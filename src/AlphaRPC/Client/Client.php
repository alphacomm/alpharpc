<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Client
 */

namespace AlphaRPC\Client;

use AlphaRPC\Client\Exception\InvalidResponseException;
use AlphaRPC\Client\Protocol\ExecuteRequest;
use AlphaRPC\Client\Protocol\ExecuteResponse;
use AlphaRPC\Client\Protocol\FetchRequest;
use AlphaRPC\Client\Protocol\FetchResponse;
use AlphaRPC\Client\Protocol\TimeoutResponse;
use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\Serialization\PhpSerializer;
use AlphaRPC\Common\Serialization\SerializerInterface;
use AlphaRPC\Common\Socket\Socket;
use AlphaRPC\Common\TimeoutException;
use AlphaRPC\Common\Timer\TimeoutTimer;
use AlphaRPC\Common\Timer\TimerInterface;
use AlphaRPC\Common\Timer\UnlimitedTimer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use ZMQ;
use ZMQContext;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Client
 */
class Client implements LoggerAwareInterface
{
    /**
     * List addresses where alpharpc instances are available.
     *
     * @var ManagerList
     */
    protected $managerList;

    /**
     * Serializer used to send data over the sockets. Must match the worker
     * serializer.
     *
     * @var SerializerInterface
     */
    protected $serializer = null;

    /**
     * Sockets used by the client.
     *
     * @var Socket[]
     */
    protected $socket = array();

    /**
     * Function called when a new log message is created.
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Timeout in MS.
     *
     * @var int
     */
    protected $timeout;

    /**
     * Time it takes for the manager to respond in MS.
     *
     * @var int
     */
    protected $delay;

    /**
     * Creates an instance.
     *
     * @param array|null $config
     *     Available configuration options:
     *     - managerResponseTimeout
     *     - serializer
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = array();
        }

        if (isset($config['logger'])) {
            $this->setLogger($config['logger']);
        }

        if (!isset($config['timeout'])) {
            $config['timeout'] = AlphaRPC::MAX_CLIENT_TIMEOUT;
        }
        $this->setTimeout($config['timeout']);

        if (!isset($config['delay'])) {
            $config['delay'] = AlphaRPC::MAX_MANAGER_DELAY;
        }
        $this->setDelay($config['delay']);

        if (!isset($config['serializer'])) {
            $config['serializer'] = new PhpSerializer();
        }
        $this->setSerializer($config['serializer']);

        $this->managerList = new ManagerList();
    }

    /**
     * Defines how long the client should wait for the AlphaRPC instances
     * to respond. If the time is reached the next AlphaRPC instance will be
     * asked to execute the request.
     *
     * @param integer $delay
     *
     * @return Client
     * @throws \RuntimeException
     */
    public function setDelay($delay)
    {
        if (is_string($delay) && ctype_digit($delay)) {
            $delay = (int) $delay;
        } elseif (!is_int($delay)) {
            throw new \RuntimeException('Delay should be an integer in MS.');
        }

        $this->delay = $delay;

        return $this;
    }

    /**
     * Returns the manager response delay in MS.
     *
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Returns the serializer.
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Sets the serializer.
     *
     * @param SerializerInterface $serializer
     *
     * @return Client
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Gets the timeout in MS.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the timeout in MS.
     *
     * @param int $timeout
     *
     * @return Client
     * @throws \RuntimeException
     */
    public function setTimeout($timeout)
    {
        if (is_string($timeout) && ctype_digit($timeout)) {
            $timeout = (int) $timeout;
        } elseif (!is_int($timeout)) {
            throw new \RuntimeException('Unable to set timeout.');
        }
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Adds an address to a AlphaRPC daemon.
     *
     * @param string|array $manager
     *
     * @return Client
     * @throws \InvalidArgumentException
     */
    public function addManager($manager)
    {
        if (is_array($manager)) {
            foreach ($manager as $item) {
                $this->managerList->add($item);
            }

            return $this;
        }

        $this->managerList->add($manager);

        return $this;
    }

    /**
     * Starts a request.
     *
     * @param string $function
     * @param array  $params
     * @param string $cache
     *
     * @return Request
     * @throws \RuntimeException
     */
    public function startRequest($function, array $params = array(), $cache = null)
    {
        if ($cache !== null && !is_string($cache)) {
            throw new \RuntimeException('$cache is an id for the request and should be a string or null.');
        }

        $request = new Request($function, $params);
        $prioritizedManagerList = $this->managerList->toPrioritizedArray();

        foreach ($prioritizedManagerList as $manager) {
            try {
                $response = $this->sendExecuteRequest($manager, $request, $cache);

                $request->setManager($manager);
                $request->setRequestId($response->getRequestId());
                $this->managerList->flag($manager, ManagerList::FLAG_AVAILABLE);

                $this->getLogger()->debug($manager.' accepted request '.$response->getRequestId().' '.$request->getFunction());

                return $request;
            } catch (TimeoutException $ex) {
                $this->managerList->flag($manager, ManagerList::FLAG_UNAVAILABLE);

                $this->getLogger()->notice($manager.': '.$ex->getMessage());
            }
        }

        throw new RuntimeException('AlphaRPC ('.implode(', ', $prioritizedManagerList).') did not respond in time.');
    }

    /**
     * Does a full request and returns the result.
     *
     * @param string $function
     * @param array  $params
     * @param string $cache
     *
     * @return mixed
     */
    public function request($function, array $params = array(), $cache = null)
    {
        $request = $this->startRequest($function, $params, $cache);

        return $this->fetchResponse($request);
    }

    /**
     * Returns a socket for the given server.
     *
     * If the socket does not yet exist, it is created.
     *
     * @param string $server
     *
     * @return Socket
     */
    protected function getSocket($server)
    {
        if (isset($this->socket[$server])) {
            return $this->socket[$server];
        }

        $context = new ZMQContext();
        $socket = Socket::create($context, ZMQ::SOCKET_REQ, $this->logger);
        $socket->connect($server);

        $this->socket[$server] = $socket;

        return $socket;
    }

    /**
     * Invalidate the socket for a specific manager.
     *
     * @param string $manager
     *
     * @return void
     */
    protected function invalidateSocket($manager)
    {
        if (isset($this->socket[$manager])) {
            unset($this->socket[$manager]);
        }
    }

    /**
     * Serializes parameters.
     *
     * @param array $params
     *
     * @return array
     */
    protected function serializeParams(array $params)
    {
        $serialized = array();
        foreach ($params as $param) {
            $serialized[] = $this->serializer->serialize($param);
        }

        return $serialized;
    }

    /**
     * Send a request to the ClientHandler.
     *
     * @param string  $manager
     * @param Request $request
     * @param string  $cache
     *
     * @return ExecuteResponse
     * @throws TimeoutException
     * @throws InvalidResponseException
     */
    protected function sendExecuteRequest($manager, Request $request, $cache = null)
    {
        $serialized = $this->serializeParams($request->getParams());
        $msg = new ExecuteRequest($cache, $request->getFunction(), $serialized);

        $socket = $this->getSocket($manager);
        $stream = $socket->getStream();
        $stream->send($msg);

        try {
            $response = $stream->read(new TimeoutTimer($this->delay));
        } catch (TimeoutException $ex) {
            $this->invalidateSocket($manager);

            throw $ex;
        }

        if (!$response instanceof ExecuteResponse) {
            $msg = $manager.': Invalid response '.get_class($response).'.';
            $this->getLogger()->notice($msg);
            throw new InvalidResponseException($msg);
        }

        if ('' != $response->getResult()) {
            $this->handleFetchResponse($response, $request);
        }

        return $response;
    }

    /**
     * Fetches the response for a request.
     *
     * Throws a RuntimeException if there is no response (yet) and the timeout
     * is reached.
     *
     * @param \AlphaRPC\Client\Request $request
     * @param boolean                  $waitForResult
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function fetchResponse(Request $request, $waitForResult = true)
    {
        if ($request->hasResponse()) {
            return $request->getResponse();
        }

        $timer = $this->getFetchTimer($waitForResult);
        $req = new FetchRequest($request->getRequestId(), $waitForResult);

        do {
            try {
                $response = $this->sendFetchRequest($this->getManagerForRequest($request), $req);
                $result = $this->handleFetchResponse($response, $request);

                return $result;
            } catch (TimeoutException $ex) {
                $this->getLogger()->debug($request->getRequestId().': '.$ex->getMessage());
            }
        } while (!$timer->isExpired());

        throw new TimeoutException('Request '.$request->getRequestId().' timed out.');
    }

    /**
     * @param FetchResponse $response
     * @param Request       $request
     *
     * @return mixed
     */
    private function handleFetchResponse(FetchResponse $response, Request $request)
    {
        $rawResult = $response->getResult();
        $this->getLogger()->info('Received result: '.$rawResult.' for request: '.$request->getRequestId().'.');
        $result = $this->serializer->unserialize($rawResult);
        $request->setResponse($result);

        return $result;
    }

    /**
     * @param boolean $waitForResult
     *
     * @return TimerInterface
     */
    protected function getFetchTimer($waitForResult)
    {
        if (!$waitForResult) {
            return new TimeoutTimer($this->delay);
        }

        if ($this->timeout == -1) {
            return new UnlimitedTimer();
        }

        return new TimeoutTimer($this->timeout);
    }

    /**
     *
     * @param string       $manager
     * @param FetchRequest $request
     *
     * @return FetchResponse
     * @throws InvalidResponseException
     * @throws RuntimeException
     */
    protected function sendFetchRequest($manager, FetchRequest $request)
    {
        $socket = $this->getSocket($manager);
        $stream = $socket->getStream();

        $stream->send($request);

        try {
            $response = $stream->read(new TimeoutTimer($this->delay + AlphaRPC::CLIENT_PING));
        } catch (TimeoutException $ex) {
            $this->invalidateSocket($manager);

            throw $ex;
        }

        if ($response instanceof TimeoutResponse) {
            throw new TimeoutException('The request timed out.');
        }

        if (!$response instanceof FetchResponse) {
            throw new InvalidResponseException('Invalid response: '.get_class($response));
        }

        $requestId = $response->getRequestId();
        if ($requestId !== $request->getRequestId()) {
            throw new InvalidResponseException('Result for unexpected request: '.$requestId.', expecting: '.$request->getRequestId().'.');
        }

        return $response;
    }

    /**
     * Set the logger.
     *
     * The logger should be a callable that accepts 2 parameters, a
     * string $message and an integer $priority.
     *
     * @param LoggerInterface $logger
     *
     * @return Client
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Returns a manager for the Request.
     *
     * It prefers the manager that is already set, but if it is
     * not, then it adds a random manager from the manager list.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getManagerForRequest(Request $request)
    {
        if (!$request->getManager()) {
            $request->setManager($this->managerList->get());
        }

        return $request->getManager();
    }

}
