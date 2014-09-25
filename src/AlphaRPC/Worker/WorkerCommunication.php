<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Worker
 */

namespace AlphaRPC\Worker;

use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\MessageStream\MessageEvent;
use AlphaRPC\Common\MessageStream\StreamInterface;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Socket\Message;
use AlphaRPC\Common\Socket\Socket;
use AlphaRPC\Common\Socket\Stream;
use AlphaRPC\Worker\Protocol\ActionListRequest;
use AlphaRPC\Worker\Protocol\ActionListResponse;
use AlphaRPC\Worker\Protocol\ActionNotFound;
use AlphaRPC\Worker\Protocol\Destroy;
use AlphaRPC\Worker\Protocol\ExecuteJobRequest;
use AlphaRPC\Worker\Protocol\ExecuteJobResponse;
use AlphaRPC\Worker\Protocol\GetJobRequest;
use AlphaRPC\Worker\Protocol\HeartbeatResponseWorkerhandler;
use AlphaRPC\Worker\Protocol\Register;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ZMQ;
use ZMQPoll;

/**
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Worker
 */
class WorkerCommunication implements LoggerAwareInterface
{
    /**
     * The domain model/state of the worker.
     *
     * @var Worker
     */
    protected $worker = null;
    /**
     * The Logger.
     *
     * @var callable|null
     */
    protected $logger = null;

    /**
     * Contains the communication stream with the Worker Handler.
     *
     * @var Stream
     */
    protected $workerHandlerStream;

    /**
     * Contains the request / response status of the Worker Handler stream.
     *
     * @var boolean
     */
    protected $workerHandlerReady;

    /**
     * Contains the communication stream with the Service process.
     *
     * @var Stream
     */
    protected $serviceStream;

    /**
     * Contains the request / response status of the Service Stream.
     *
     * @var boolean
     */
    protected $serviceStreamReady;

    /**
     * @var ZMQPoll
     */
    protected $poll;

    /**
     * The time it takes for the handler to respond.
     *
     * @var int
     */
    protected $delay = AlphaRPC::MAX_MANAGER_DELAY;

    /**
     * @param Worker $worker
     * @param Socket $workerHandlerSocket
     * @param Socket $serviceSocket
     */
    public function __construct(Worker $worker, Socket $workerHandlerSocket, Socket $serviceSocket)
    {
        $this->worker = $worker;
        $this->createWorkerHandlerStream($workerHandlerSocket);
        $this->createServiceStream($serviceSocket);
    }

    /**
     * The time it takes for the handler to respond.
     *
     * @param int $delay
     *
     * @throws \InvalidArgumentException
     * @return WorkerCommunication
     */
    public function setDelay($delay)
    {
        if (!ctype_digit((string) $delay)) {
            throw new \InvalidArgumentException('Delay must be a number.');
        }
        $this->delay = $delay;

        return $this;
    }

    /**
     * Initializes the worker.
     */
    public function start()
    {
        $this->workerHandlerReady = true;
        $this->serviceStreamReady = true;
        $this->sendToService(new ActionListRequest());
    }

    /**
     * Perform one service cycle.
     * This method checks whether there are any new requests to be handled,
     * either from the Worker Handler or from the Service.
     */
    public function process()
    {
        if ($this->worker->getState() == Worker::INVALID) {
            throw new \RuntimeException('Invalid state to process.');
        }

        $expiry = 5000 + $this->delay;
        if ($this->worker->isExpired($expiry)) {
            $this->getLogger()->debug('Worker is expired, no longer registered to workerhandler.');
            $this->worker->setState(Worker::INVALID);

            return;
        }

        try {
            $read = $write = array();
            $this->getPoller()->poll($read, $write, 100);
        } catch (\ZMQPollException $ex) {
            pcntl_signal_dispatch();

            return;
        }

        $this->workerHandlerStream->handle();
        $this->serviceStream->handle();
        $this->handleResult();
        $this->heartbeat();
        pcntl_signal_dispatch();
    }

    /**
     * Creates a socket to communicate with the worker handler.
     *
     * @param Socket $socket
     *
     * @return null
     */
    protected function createWorkerHandlerStream(Socket $socket)
    {
        $socket->setId('manager');
        $this->getPoller()->add($socket, ZMQ::POLL_IN);

        $that = $this;
        $this->workerHandlerStream = $socket->getStream();
        $this->workerHandlerStream->addListener(StreamInterface::MESSAGE, function (MessageEvent $event) use ($that) {
            if (null === $event->getProtocolMessage()) {
                $that->getLogger()->error('Recieved an unsuported message from worker handler.', array(
                    'message' => $event->getMessage()->toArray(),
                ));

                return;
            }

            $that->onWorkerHandlerMessage($event->getProtocolMessage());
        });
    }

    /**
     * Creates a socket to communicate with the service.
     *
     * @param Socket $socket
     *
     * @return null
     */
    protected function createServiceStream(Socket $socket)
    {
        $socket->setId('worker_service');
        $this->getPoller()->add($socket, ZMQ::POLL_IN);

        $that = $this;
        $this->serviceStream = $socket->getStream();
        $this->serviceStream->addListener(StreamInterface::MESSAGE, function (MessageEvent $event) use ($that) {
            if (null === $event->getProtocolMessage()) {
                $that->getLogger()->error('Recieved an unsuported message from worker handler.', array(
                    'message' => $event->getMessage()->toArray(),
                ));

                return;
            }

            $that->onServiceMessage($event->getProtocolMessage());
        });
    }

    /**
     * Returns the poller, creates one if not exists.
     *
     * @return ZMQPoll
     */
    protected function getPoller()
    {
        if ($this->poll === null) {
            $this->poll = new ZMQPoll();
        }

        return $this->poll;
    }

    /**
     * Handles a Message from the Worker Handler.
     *
     * @param MessageInterface $msg
     */
    public function onWorkerHandlerMessage(MessageInterface $msg)
    {
        $this->workerHandlerReady = true;
        $this->worker->touch();

        if ($msg instanceof Destroy) {
            $this->worker->setState(Worker::INVALID);
        } elseif ($msg instanceof ExecuteJobRequest) {
            $this->startJob($msg);
        } elseif ($msg instanceof HeartbeatResponseWorkerhandler) {
            switch ($this->worker->getState()) {
                case Worker::READY:
                    $this->sendToWorkerHandler(new GetJobRequest());
                    break;
                case Worker::REGISTER:
                    $this->worker->setState(Worker::READY);
                    $this->sendToWorkerHandler(new GetJobRequest());
                    break;
                case Worker::RESULT:
                    $this->worker->cleanup();
                    $this->sendToWorkerHandler(new GetJobRequest());
                    break;
                case Worker::RESULT_AVAILABLE:
                    $this->handleResult();
                    break;
                case Worker::SHUTDOWN:
                    $this->worker->setState(Worker::INVALID);
                    break;
                case Worker::BUSY:
                    // Do nothing. Why?
                    break;
                default:
                    $this->getLogger()->error('Invalid worker state for OK status: '.$this->worker->getState().'.');
            }
        } elseif ($msg instanceof Message) {
            $status = $msg->shift();

            $this->getLogger()->debug('Received status from worker handler: '.$status);
            switch ($status) {
                case AlphaRPC::STATUS_OK:
                    $this->workerHandlerReply($msg);
                    break;
                default:
            }
        } else {
            $this->getLogger()->error('Unknown response message '.get_class($msg));
        }
    }

    /**
     * Handles a Message from the Service Handler.
     *
     * @param MessageInterface $msg
     */
    public function onServiceMessage(MessageInterface $msg)
    {
        $this->serviceStreamReady = true;

        $this->getLogger()->debug('Received Service Message of Type: '.get_class($msg));

        if ($msg instanceof ActionListResponse) {
            $actions = $msg->getActions();
            $this->getLogger()->debug('The action list is: '.implode(', ', $actions).'.');
            $this->worker->setActions($actions);

            $reply = new Register();
            $reply->setActions($actions);
            $this->sendToWorkerHandler($reply);
            unset($reply);

            return;
        }

        if ($msg instanceof ExecuteJobResponse) {
            $requestId = $msg->getRequestId();
            $result    = $msg->getResult();
            $this->getLogger()->debug('Result for: '.$requestId.': '.$result);
            $this->worker->setResult($requestId, $result);

            return;
        }

        $this->getLogger()->error('Unknown service response.');
    }

    /**
     * Starts a Job.
     *
     * @param ExecuteJobRequest $request
     */
    public function startJob(ExecuteJobRequest $request)
    {
        $this->worker->setState(Worker::BUSY);

        $requestId = $request->getRequestId();
        $action    = $request->getAction();
        if (!$this->worker->hasAction($action)) {
            $this->getLogger()->debug('Action '.$action.' not found.');
            $this->sendToWorkerHandler(new ActionNotFound($requestId, $action));

            return;
        }

        $this->getLogger()->debug('Sending new request ('.$requestId.') to action '.$action);
        $this->serviceStream->send($request);
    }

    /**
     * Performs a heartbeat with the Worker Handler.
     */
    public function heartbeat()
    {
        $state = $this->worker->getState();
        if (!in_array($state, array(Worker::BUSY, Worker::READY))) {
            return;
        }

        // Detect manager crash.
        $timeout = 2500;
        if (!$this->worker->isExpired($timeout)) {
            // Worker is pretty active no heartbeat required.
            return;
        }

        if ($this->isWorkerHandlerReady()) {
            // Send a heartbeat.
            $this->sendToWorkerHandler(new Protocol\Heartbeat());

            return;
        }

        $expiry = 5000 + $this->delay;
        if (!$this->worker->isExpired($expiry)) {
            return;
        }

        // Expired, manager already destroyed this instance.
        $this->worker->setState(Worker::INVALID);
    }

    /**
     * Sends when the Service is done working, this sends the result to the Worker Handler.
     */
    public function handleResult()
    {
        // No result
        $result = $this->worker->getResult();
        if ($result === null) {
            return;
        }

        // Invalid worker state, wait for a response first.
        if (!$this->isWorkerHandlerReady()) {
            return;
        }

        $this->sendToWorkerHandler(new Protocol\JobResult($this->worker->getRequestId(), $result));
        $this->worker->setState(Worker::RESULT);
    }

    /**
     * Send the given Message to the Worker Handler.
     *
     * @param MessageInterface $msg
     */
    public function sendToWorkerHandler(MessageInterface $msg)
    {
        if (!$this->isWorkerHandlerReady()) {
            $this->getLogger()->debug('WorkerHandler socket not ready to send.');

            return;
        }

        $this->workerHandlerReady = false;
        $this->workerHandlerStream->send($msg);
    }

    /**
     * Send the given Message to the Service.
     *
     * @param MessageInterface $msg
     */
    public function sendToService(MessageInterface $msg)
    {
        if (!$this->isServiceStreamReady()) {
            $this->getLogger()->debug('Service socket not ready to send.');

            return;
        }

        $this->getLogger()->debug('Sending to service.');
        $this->serviceStreamReady = false;
        $this->serviceStream->send($msg);
    }

    /**
     * Whether this Worker is ready to communicate with the WorkerHandler.
     *
     * @return boolean
     */
    public function isWorkerHandlerReady()
    {
        return $this->workerHandlerReady;
    }

    /**
     * Whether this Worker is ready to communicate with the Service.
     *
     * @return boolean
     */
    public function isServiceStreamReady()
    {
        return $this->serviceStreamReady;
    }

    /**
     * Set the logger.
     *
     * The logger should be a callable that accepts 2 parameters, a
     * string $message and an integer $priority.
     *
     * @param LoggerInterface $logger
     *
     * @return AlphaRPC
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        if ($this->serviceStream instanceof LoggerAwareInterface) {
            $this->serviceStream->setLogger($this->getLogger());
        }

        if ($this->workerHandlerStream instanceof LoggerAwareInterface) {
            $this->workerHandlerStream->setLogger($this->getLogger());
        }

        return $this;
    }

    /**
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = new \Psr\Log\NullLogger();
        }

        return $this->logger;
    }
}
