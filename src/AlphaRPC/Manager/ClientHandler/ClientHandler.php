<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */

namespace AlphaRPC\Manager\ClientHandler;

use AlphaRPC\Client\Protocol\ExecuteRequest;
use AlphaRPC\Client\Protocol\ExecuteResponse;
use AlphaRPC\Client\Protocol\FetchRequest;
use AlphaRPC\Client\Protocol\FetchResponse;
use AlphaRPC\Client\Protocol\PoisonResponse;
use AlphaRPC\Client\Protocol\TimeoutResponse;
use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\MessageStream\MessageEvent;
use AlphaRPC\Common\MessageStream\StreamInterface;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Timer\TimeoutTimer;
use AlphaRPC\Manager\Protocol\ClientHandlerJobRequest;
use AlphaRPC\Manager\Protocol\ClientHandlerJobResponse;
use AlphaRPC\Manager\Protocol\WorkerHandlerStatus;
use AlphaRPC\Manager\Request;
use AlphaRPC\Manager\Storage\AbstractStorage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */
class ClientHandler implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var StreamInterface[]
     */
    protected $streams = array();

    /**
     *
     * @var AbstractStorage
     */
    protected $storage;

    /**
     *
     * @var Request[]
     */
    protected $request = array();

    /**
     *
     * @var ClientBucket
     */
    protected $clients = null;

    /**
     *
     * @var int[]
     */
    protected $workerHandlers = array();

    /**
     *
     * @var string[]
     */
    protected $workerHandlerQueue = array();

    /**
     * Can we send to the worker socket?
     *
     * @var boolean
     */
    protected $workerHandlerReady = true;

    /**
     * @param StreamInterface $clientStream
     * @param StreamInterface $workerHandlerStream
     * @param StreamInterface $workerHandlerStatusStream
     * @param AbstractStorage $storage
     * @param LoggerInterface $logger
     */
    public function __construct(
        StreamInterface $clientStream,
        StreamInterface $workerHandlerStream,
        StreamInterface $workerHandlerStatusStream,
        AbstractStorage $storage,
        LoggerInterface $logger = null
    ) {
        $this->storage = $storage;
        $this->clients = new ClientBucket();

        $this->setLogger($logger);

        $this->setStream('client',              $clientStream);
        $this->setStream('workerHandler',       $workerHandlerStream);
        $this->setStream('workerHandlerStatus', $workerHandlerStatusStream);
    }

    /**
     * Add the given stream and register it to the EventDispatcher.
     *
     * @param string          $type
     * @param StreamInterface $stream
     *
     * @return ClientHandler
     */
    protected function setStream($type, StreamInterface $stream)
    {
        $this->streams[$type] = $stream;

        $callback = array($this, 'on'.ucfirst($type).'Message');

        $stream->addListener(
            StreamInterface::MESSAGE,
            $this->createEventListenerForStream($callback)
        );

        return $this;
    }

    /**
     * Creates an event listener for a stream.
     *
     * @param callable $callback
     *
     * @return callable
     */
    private function createEventListenerForStream($callback)
    {
        $logger = $this->getLogger();

        $function = function (MessageEvent $event) use ($callback, $logger) {
            $protocol = $event->getProtocolMessage();

            if ($protocol === null) {
                $logger->debug('Incompatable message: '.$event->getMessage());

                return;
            }

            $routing = $event->getMessage()->getRoutingInformation();

            call_user_func($callback, $protocol, $routing);

            return;
        };

        return $function;
    }

    /**
     * Returns the requested Stream.
     *
     * @param string $type
     *
     * @return StreamInterface
     */
    protected function getStream($type)
    {
        return $this->streams[$type];
    }

    /**
     * Handle a message from a Client.
     *
     * @param MessageInterface $msg
     * @param array            $routing
     */
    public function onClientMessage(MessageInterface $msg, $routing)
    {
        $client = $this->client(array_shift($routing));

        if ($msg instanceof ExecuteRequest) {
            $this->clientRequest($client, $msg);

            return;
        } elseif ($msg instanceof FetchRequest) {
            $this->clientFetch($client, $msg);

            return;
        }

        $this->getLogger()->info('Invalid message type: '.get_class($msg).'.');
    }

    /**
     * Handle the result of a Job that a WorkerHandler processed.
     *
     * @param ClientHandlerJobResponse $msg
     */
    public function onWorkerHandlerMessage(ClientHandlerJobResponse $msg)
    {
        $this->workerHandlerReady = true;

        $requestId = $msg->getRequestId();
        if (isset($this->workerHandlerQueue[$requestId])) {
            unset($this->workerHandlerQueue[$requestId]);
        }

        $workerHandler = $msg->getWorkerHandlerId();
        $request = $this->getRequest($requestId);
        if ($request === null) {
            $this->getLogger()->info(
                'Worker-handler '.$workerHandler.' accepted request: '
                .$requestId.', but request state is unknown.');

            return;
        }

        $this->getLogger()->debug(
            'Worker-handler '.$workerHandler.' accepted request: '.$requestId.'.');

        $request->setWorker($workerHandler);
    }

    /**
     * Handle a status message from the WorkerHandler.
     *
     * @param WorkerHandlerStatus $msg
     */
    public function onWorkerHandlerStatusMessage(WorkerHandlerStatus $msg)
    {
        $handlerId = $msg->getWorkerHandlerId();
        if (isset($this->workerHandlers[$handlerId])) {
            // Remove to make sure the handlers are ordered by time.
            unset($this->workerHandlers[$handlerId]);
        }
        $this->workerHandlers[$handlerId] = microtime(true);

        $requestId = $msg->getRequestId();
        if ($requestId !== null) {
            $this->getLogger()->debug(
                'Storage has a result available for: '.$requestId.'.');

            $this->sendResponseToClients($requestId);
        }
    }

    /**
     * Processes all new messages in the WorkerHandler queue.
     */
    public function handleWorkerHandlerQueue()
    {
        if (!$this->workerHandlerReady || count($this->workerHandlerQueue) == 0) {
            return;
        }
        $requestId = array_shift($this->workerHandlerQueue);
        $request = $this->getRequest($requestId);
        if ($request === null) {
            return;
        }

        $this->getLogger()->debug(
            'Sending request: '.$requestId.' to worker-handler.');

        $this->workerHandlerReady = false;
        $this->getStream('workerHandler')->send(
            new ClientHandlerJobRequest($requestId, $request->getActionName(), $request->getParams())
        );
    }

    /**
     * Checks whether the given WorkerHandler exists.
     *
     * @param $id
     *
     * @return boolean
     */
    public function hasWorkerHandler($id)
    {
        return isset($this->workerHandlers[$id]);
    }

    /**
     * Checks and removes all expired WorkerHandlers.
     *
     * @return boolean
     */
    public function hasExpiredWorkerHandler()
    {
        $hasExpired = false;
        $timeout = AlphaRPC::WORKER_HANDLER_TIMEOUT;
        $validTime = microtime(true) - ($timeout/1000);
        foreach ($this->workerHandlers as $handlerId => $time) {
            if ($time >= $validTime) {
                break;
            }
            unset($this->workerHandlers[$handlerId]);
            $hasExpired = true;
        }

        return $hasExpired;
    }

    /**
     * Handle a request form a Client.
     *
     * @param Client         $client
     * @param ExecuteRequest $msg
     */
    public function clientRequest(Client $client, ExecuteRequest $msg)
    {
        $requestId = $msg->getRequestId();
        if (!$requestId) {
            // No requestId given, generate a unique one.
            do {
                $requestId = sha1(uniqid());
            } while (isset($this->request[$requestId]));
        }

        $request = $this->getRequest($requestId);
        if (!$request) {
            $action = $msg->getAction();
            $params = $msg->getParams();

            $this->getLogger()->info('New request: '.$requestId.' from client: '
                .bin2hex($client->getId()).' for action '.$action.'.');

            $this->addRequest(new Request($requestId, $action, $params));
            if (!isset($this->storage[$requestId])) {
                $this->addWorkerQueue($requestId);
            }
        } else {
            $this->getLogger()->info('Known request: '.$requestId.' from client: '
                .bin2hex($client->getId()).'.');
        }

        $this->reply($client, new ExecuteResponse($requestId));
    }

    /**
     * Handle a Fetch from a client.
     *
     * @param Client       $client
     * @param FetchRequest $msg
     */
    protected function clientFetch(Client $client, FetchRequest $msg)
    {
        $requestId = $msg->getRequestId();
        $waitForResult = $msg->getWaitForResult();

        $this->getLogger()->debug('Client: '.bin2hex($client->getId()).' is requesting'
            .' the result of request: '.$requestId.' wait: '
            .($waitForResult ? 'yes' : 'no'));
        $client->setRequest($requestId, $waitForResult);
        if (isset($this->storage[$requestId])) {
            $this->sendResponseToClients($requestId);
        } elseif (!$waitForResult) {
            $this->logger->debug(
                'No result for '.$requestId.' and client is not willing to wait.'
            );
            $this->reply($client, new TimeoutResponse($requestId));
        }
    }

    /**
     *
     * @param string $requestId
     *
     * @return null
     */
    protected function sendResponseToClients($requestId)
    {
        if (!isset($this->storage[$requestId])) {
            $this->getLogger()->notice(
                'Storage does not have a result for request: '.$requestId.'.'
            );

            return;
        }

        $result = $this->storage[$requestId];
        $clients = $this->getClientsForRequest($requestId);
        $this->removeRequest($requestId);
        $msg = new FetchResponse($requestId, $result);

        /*
         * Check for the magic word "STATUS:" that indicates the job did
         * not get an actual result. Format: STATUS:CODE
         * TODO: Fix this.
         */
        if (substr($result, 0, 7) == 'STATUS:') {
            $parts = explode(':', $result, 3);
            $code = (int) $parts[1];
            if ($code === 500) {
                $msg = new PoisonResponse($requestId);
            }
        }

        $clientIds = array();
        foreach ($clients as $client) {
            $this->reply($client, $msg);
            $clientIds[] = bin2hex($client->getId());
        }

        $this->getLogger()->info(
            'Sending result for request '.$requestId.' to '
            .' client(s): '.implode(', ', $clientIds).'.'
        );
    }

    /**
     * Contains the main loop of the Client Handler.
     *
     * This checks and handles new messages on the Client Handler sockets.
     */
    public function handle()
    {
        $this->getStream('client')->handle(new TimeoutTimer(AlphaRPC::MAX_MANAGER_DELAY/4));
        $this->getStream('workerHandler')->handle(new TimeoutTimer(AlphaRPC::MAX_MANAGER_DELAY/4));
        $this->handleWorkerHandlerQueue();
        $this->getStream('workerHandlerStatus')->handle(new TimeoutTimer(AlphaRPC::MAX_MANAGER_DELAY/4));
        $this->handleExpired();
        $this->handleExpiredWorkerHandlers();
    }

    /**
     * Send a reply to expired clients.
     */
    public function handleExpired()
    {
        $expired = $this->clients->getExpired(AlphaRPC::CLIENT_PING);
        foreach ($expired as $client) {
            $this->reply($client, new TimeoutResponse());
        }
    }

    /**
     * Queue requests again for expired WorkerHandlers.
     */
    public function handleExpiredWorkerHandlers()
    {
        if ($this->hasExpiredWorkerHandler()) {
            foreach ($this->request as $request) {
                $worker = $request->getWorker();
                if (!$this->hasWorkerHandler($worker)) {
                    $this->getLogger()->info(
                        'Worker-handler for request: '.$request->getId()
                        .' is expired, the request is queued again.'
                    );
                    $this->addWorkerQueue($request->getId());
                }
            }
        }
    }

    /**
     * Returns the Request with the given ID.
     *
     * @param string $id
     *
     * @return null|Request
     */
    public function getRequest($id)
    {
        if (!isset($this->request[$id])) {
            return null;
        }

        return $this->request[$id];
    }

    /**
     * Add a request with the given ID.
     *
     * @param Request $request
     *
     * @return ClientHandler
     */
    public function addRequest(Request $request)
    {
        $this->request[$request->getId()] = $request;

        return $this;
    }

    /**
     * Remove the given request.
     *
     * @param string $requestId
     *
     * @return $this
     */
    public function removeRequest($requestId)
    {
        if (isset($this->request[$requestId])) {
            unset($this->request[$requestId]);
        }

        return $this;
    }

    /**
     * Add a request ID to the WorkerHandler queue.
     *
     * @param string $requestId
     *
     * @return ClientHandler
     */
    public function addWorkerQueue($requestId)
    {
        $this->workerHandlerQueue[$requestId] = $requestId;

        return $this;
    }

    /**
     * Returns a list of Clients for the given Request.
     *
     * @param string $requestId
     *
     * @return Client[]
     */
    public function getClientsForRequest($requestId)
    {
        return $this->clients->getClientsForRequest($requestId);
    }

    /**
     * Returns the client with the given ID.
     *
     * @param string $id
     *
     * @return Client
     */
    public function client($id)
    {
        return $this->clients->client($id);
    }

    /**
     * Remove the given Client.
     *
     * @param Client $client
     */
    public function remove(Client $client)
    {
        $this->clients->remove($client);
    }

    /**
     * Send the given message to the Client.
     *
     * @param Client           $client
     * @param MessageInterface $msg
     */
    public function reply(Client $client, MessageInterface $msg)
    {
        $this->getStream('client')->send($msg, $client->getId());
        $this->remove($client);
    }

    /**
     * Set the Logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->getLogger()->info('ClientHandler is started with pid '.getmypid());
    }

    /**
     * Returns the Logger
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
