<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage WorkerHandler
 */

namespace AlphaRPC\Manager\WorkerHandler;

use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\MessageStream\MessageEvent;
use AlphaRPC\Common\MessageStream\StreamInterface;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Timer\TimeoutTimer;
use AlphaRPC\Manager\Protocol\ClientHandlerJobRequest;
use AlphaRPC\Manager\Protocol\ClientHandlerJobResponse;
use AlphaRPC\Manager\Protocol\QueueStatusRequest;
use AlphaRPC\Manager\Protocol\QueueStatusResponse;
use AlphaRPC\Manager\Protocol\WorkerHandlerStatus;
use AlphaRPC\Manager\Protocol\WorkerStatusRequest;
use AlphaRPC\Manager\Protocol\WorkerStatusResponse;
use AlphaRPC\Manager\Request;
use AlphaRPC\Manager\Storage\AbstractStorage;
use AlphaRPC\Worker\Protocol\Destroy;
use AlphaRPC\Worker\Protocol\ExecuteJobRequest;
use AlphaRPC\Worker\Protocol\GetJobRequest;
use AlphaRPC\Worker\Protocol\Heartbeat;
use AlphaRPC\Worker\Protocol\HeartbeatResponseWorkerhandler;
use AlphaRPC\Worker\Protocol\JobResult;
use AlphaRPC\Worker\Protocol\Register;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage WorkerHandler
 */
class WorkerHandler implements LoggerAwareInterface
{
    /**
     * The unique identifier for this workerhandler. Used for crash detection.
     *
     * @var string
     */
    protected $id;

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
     * @var Worker[]
     */
    protected $workers = array();

    /**
     *
     * @var Action[]
     */
    protected $actionList = array();

    /**
     *
     * @var Request[]
     */
    protected $clientRequests = array();

    /**
     * Contains the Logger.
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     *
     * @var string
     */
    protected $handlerId;

    /**
     * List of actions that are not available right now.
     *
     * @var array
     */
    protected $notAvailable = array();

    /**
     *
     * @param StreamInterface $workerStream
     * @param StreamInterface $statusStream
     * @param StreamInterface $clientStream
     * @param AbstractStorage $storage
     * @param LoggerInterface $logger
     */
    public function __construct(
        StreamInterface $workerStream, StreamInterface $statusStream,
        StreamInterface $clientStream, AbstractStorage $storage,
        LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        $this->setId(getmypid());

        $this->setStream('worker', $workerStream);
        $this->setStream('status', $statusStream);
        $this->setStream('clientHandler', $clientStream);
        $this->storage = $storage;

        $this->getLogger()->info('WorkerHandler running with id '.$this->getId());
    }

    /**
     *
     * @param string          $type
     * @param StreamInterface $stream
     *
     * @return WorkerHandler
     */
    protected function setStream($type, $stream)
    {
        $this->streams[$type] = $stream;
        $callback = array($this, 'on'.ucfirst($type).'Message');
        $logger = $this->getLogger();
        $stream->addListener(StreamInterface::MESSAGE, function(MessageEvent $event) use ($callback, $logger) {
            $protocol = $event->getProtocolMessage();
            if ($protocol === null) {
                $logger->debug('Incompatible message: '.$event->getMessage());

                return;
            }

            $routing = $event->getMessage()->getRoutingInformation();

            call_user_func($callback, $protocol, $routing);

            return;
        });

        return $this;
    }

    /**
     *
     * @param string $type
     *
     * @return StreamInterface
     */
    protected function getStream($type)
    {
        return $this->streams[$type];
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Handles a request from the ClientHandler.
     *
     * This is always a ClientHandlerJobRequest.
     *
     * @param ClientHandlerJobRequest $msg
     */
    public function onClientHandlerMessage(ClientHandlerJobRequest $msg)
    {
        $request = new Request($msg->getRequestId(), $msg->getActionName(), $msg->getParameters());
        $this->addRequest($request);

        $this->getStream('clientHandler')->send(
            new ClientHandlerJobResponse(
                $msg->getRequestId(),
                $this->getId()
            )
        );
    }

    public function onWorkerMessage(MessageInterface $msg, $routing)
    {
        $worker = $this->getWorker(array_shift($routing));
        $worker->touch();
        $worker->setReady();

        if ($msg instanceof Register) {
            $this->handleWorkerInit($worker, $msg);
        } elseif ($msg instanceof GetJobRequest) {
            $this->handleGetJobRequest($worker);
        } elseif ($msg instanceof JobResult) {
            $this->handleWorkerResult($worker, $msg);
        } elseif ($msg instanceof Heartbeat) {
            $this->reply($worker, new HeartbeatResponseWorkerhandler());
        } elseif ($msg instanceof QueueStatusRequest) {
            $this->queueStatus($worker);

            return;
        } elseif ($msg instanceof WorkerStatusRequest) {
            $this->workerStatus($worker);

            return;
        } else {
            $this->getLogger()->notice('Invalid Message of type '.get_class($msg).'.');
            $worker->setValid(false);
        }

        // Notify the actions that the worker is waiting or destroyed.
        $this->notifyActions($worker);

        if (!$worker->isValid() && $worker->isReady()) {
            $this->reply($worker, new Destroy());
        }
    }

    /**
     * Handles an init message from the worker.
     *
     * @param Worker   $worker
     * @param Register $msg
     *
     * @return void
     */
    protected function handleWorkerInit(Worker $worker, Register $msg)
    {
        $actionCount = intval(count($msg->getActions()));
        if ($actionCount <= 0) {
            $this->getLogger()->error('Invalid init, invalid action count.');

            return;
        }

        foreach ($msg->getActions() as $actionName) {
            $worker->addAction($actionName);

            $action = $this->getActionName($actionName);
            $action->addWorker($worker);
        }

        $this->getLogger()->info('New worker ('.$worker->getHexId().') with '.$actionCount.' actions.');
        $this->getLogger()->debug('Actions for worker '.$worker->getHexId().': '.implode(', ', $worker->getActionList()));

        $worker->setValid(true);

        $this->reply($worker, new HeartbeatResponseWorkerhandler());

        $this->getLogger()->debug('Worker '.bin2hex($worker->getId()).' initialised.');
    }

    /**
     * Sets the Worker object and its actions to be available again.
     *
     * @param Worker $worker
     */
    protected function handleGetJobRequest(Worker $worker)
    {
        $this->getLogger()->debug('Worker '.$worker->getHexId().' is requesting a Job.');
        if (!$worker->isValid()) {
            return;
        }
        $actions = $worker->getActionList();
        foreach ($actions as $action) {
            if (isset($this->notAvailable[$action])) {
                unset($this->notAvailable[$action]);
                $this->getLogger()->debug('Action: '.$action.' is available.');
            }
        }
    }

    public function handleWorkerResult(Worker $worker, JobResult $msg)
    {
        $this->getLogger()->debug(
            'Worker '.$worker->getHexId().' has a result for request '.
            $msg->getRequestId().'.'
        );
        $this->storeResult($worker, $msg->getRequestId(), $msg->getResult());
        $this->reply($worker, new HeartbeatResponseWorkerhandler());
    }

    public function queueStatus(Worker $worker)
    {
        // Worker needs to be set to be valid, because
        // otherwise it will receive a Destroy message.
        $worker->setValid(true);

        $queue = array();
        $busy  = array();

        $workers = $this->getWorkers();
        /* @var $w Worker */
        foreach ($workers as $w) {
            $request = $w->getRequest();
            if ($request === null) {
                continue;
            }

            $actionName = $request->getActionName();
            if (!isset($busy[$actionName])) {
                $busy[$actionName] = 0;
            }
            $busy[$actionName]++;
        }

        $requestQueue = $this->getRequestQueue();
        /* @var $request Request */
        foreach ($requestQueue as $request) {
            $actionName = $request->getActionName();
            if (!isset($queue[$actionName])) {
                $queue[$actionName] = 0;
            }
            $queue[$actionName]++;
        }

        $statusList = array();
        $actions = $this->getActionList();
        foreach ($actions as $action) {
            $actionName = $action->getName();
            if (!isset($queue[$actionName])) {
                $queue[$actionName] = 0;
            }
            if (!isset($busy[$actionName])) {
                $busy[$actionName] = 0;
            }
            $statusList[$actionName] = array(
                'action'    => $actionName,
                'queue'     => $queue[$actionName],
                'busy'      => $busy[$actionName],
                'available' => $action->cleanup()->countWorkers()
            );
        }

        $this->reply($worker, new QueueStatusResponse($statusList));
        $worker->setValid(false);
    }

    public function workerStatus(Worker $worker)
    {
        // Worker needs to be set to be valid, because
        // otherwise it will receive a Destroy message.
        $worker->setValid(true);

        $workers = $this->getWorkers();

        $workerList = array();
        /* @var $w Worker */
        foreach ($workers as $w) {
            $id = $w->getId();

            $actionList = $w->getActionList();

            $actionName = null;
            $request     = $w->getRequest();
            if ($request !== null) {
                $actionName = $request->getActionName();
            }

            $workerList[$id] = array(
                'id'         => $id,
                'actionList' => $actionList,
                'current'    => $actionName,
                'ready'      => $w->isReady(),
                'valid'      => $w->isValid(),
            );
        }

        $this->reply($worker, new WorkerStatusResponse($workerList));
        $worker->setValid(false);
    }

    /**
     * Send a reply to the Worker.
     *
     * @param Worker           $worker
     * @param MessageInterface $msg
     */
    public function reply(Worker $worker, MessageInterface $msg)
    {
        if (!$worker->isReady()) {
            $this->getLogger()->debug(
                'Worker '.$worker->getHexId().' is not ready to receive data.');

            return;
        }
        $worker->setReady(false);

        if (!$worker->isValid()) {
            $this->getLogger()->debug(
                'Worker '.$worker->getHexId().' is not valid, destroying.');
            $msg = new Destroy();
        }

        $this->getStream('worker')->send($msg, $worker->getId());
    }

    /**
     * Sends the request to the worker.
     *
     * @param Worker  $worker
     * @param Request $request
     */
    public function sendRequest(Worker $worker, Request $request)
    {
        $worker->setRequest($request);
        $this->reply(
            $worker,
            new ExecuteJobRequest(
                $request->getId(),
                $request->getActionName(),
                $request->getParams()
            )
        );
    }

    /**
     * Retrieves the worker by id, if it is not available a new one will be
     * created.
     *
     * @param string $id
     *
     * @return Worker
     */
    public function getWorker($id)
    {
        if (!isset($this->workers[$id])) {
            $this->workers[$id] = new Worker($id);
        }

        return $this->workers[$id];
    }

    /**
     *
     * @return Worker[]
     */
    public function getWorkers()
    {
        return $this->workers;
    }

    /**
     * Gets a action by name. If it does not exist it is created.
     *
     * @param string $name
     *
     * @return Action
     */
    public function getActionName($name)
    {
        if (!isset($this->actionList[$name])) {
            $this->actionList[$name] = new Action($name);
        }

        return $this->actionList[$name];
    }

    /**
     *
     * @return Action[]
     */
    public function getActionList()
    {
        return $this->actionList;
    }

    /**
     * Notify actions that the worker state is changed. If the worker is valid
     * it will be added to the actions. If it is invalid it will be removed.
     *
     * @param Worker $worker
     *
     * @return void
     */
    public function notifyActions(Worker $worker)
    {
        $actionList = $worker->getActionList();

        foreach ($actionList as $actionName) {
            $action = $this->getActionName($actionName);
            if ($worker->isReady()) {
                $action->addWaitingWorker($worker);
            } elseif (!$worker->isValid()) {
                $action->removeWorker($worker);
            }
        }
    }

    public function addRequest(Request $request)
    {
        $this->clientRequests[$request->getId()] = $request;
        $this->getLogger()->info('Received new request '.$request->getId());

        return $this;
    }

    /**
     *
     * @return Request[]
     */
    public function getRequestQueue()
    {
        return $this->clientRequests;
    }

    /**
     * Searches the requestQueue for new jobs.
     *
     * @return void
     */
    protected function handleRequests()
    {
        /* @var $request Request */
        foreach ($this->clientRequests as $requestId => $request) {
            $actionName = $request->getActionName();
            if (isset($this->notAvailable[$actionName])) {
                continue;
            }

            $action = $this->getActionName($actionName);
            $worker  = $action->fetchWaitingWorker();

            // No workers available for this request.
            if ($worker === null) {
                $this->notAvailable[$actionName] = true;
                $totalWorkers                     = $action->countWorkers();
                if ($totalWorkers > 0) {
                    $this->getLogger()->debug(
                        'All workers ('.$totalWorkers.') for'
                        .' action: "'.$actionName.'" are busy.'
                    );
                } else {
                    $this->getLogger()->notice(
                        'No workers registered for action '.$actionName.'.'
                    );
                }
                continue;
            }

            $worker->setRequest($request);
            unset($this->clientRequests[$requestId]);

            $this->sendRequest($worker, $request);
        }
    }

    /**
     * Store a result in the Storage
     *
     * @param Worker $worker
     * @param string $requestId
     * @param string $result
     */
    public function storeResult($worker, $requestId, $result)
    {
        $request = $worker->getRequest();
        if ($request === null) {
            return;
        }

        if ($request->getId() != $requestId) {
            $this->getLogger()->error('RequestId mismatch ('.$requestId.' => '.$request->getId().').');

            return;
        }

        $this->storage[$requestId] = $result;
        $worker->setRequest(null);
        $this->notify($requestId);
    }

    /**
     * Perform one round of the loop.
     */
    public function handle()
    {
        $this->getStream('clientHandler')->handle(new TimeoutTimer(AlphaRPC::WORKER_HANDLER_TIMEOUT / 3));
        $this->getStream('worker')->handle(new TimeoutTimer(AlphaRPC::WORKER_HANDLER_TIMEOUT / 3));
        $this->purgeWorkers();
        $this->handleRequests();
        $this->notify();
    }

    /**
     * Send a message to notify the client handlers that the workerhandler is
     * still alive.
     *
     * @param string|null $requestId
     *
     * @return void
     */
    public function notify($requestId = null)
    {
        $this->getStream('status')->send(
            new WorkerHandlerStatus($this->getId(), $requestId)
        );
    }

    /**
     * Purges workers
     *
     * @return void
     */
    protected function purgeWorkers()
    {
        $timeoutHeartbeat = 5000;
        $timeoutExpiry    = 10000;

        $time        = microtime(true);
        $heartbeatAt = $time - ($timeoutHeartbeat / 1000);
        $expiryAt    = $time - ($timeoutExpiry / 1000);
        /* @var $worker Worker */
        foreach ($this->workers as $id => $worker) {
            if ($expiryAt > $worker->getActivityAt()) {
                $worker->setValid(false);
                $this->getLogger()->info(
                    'Worker '.$worker->getHexId().' invalid because of a timeout.'
                );
            }

            if (!$worker->isValid()) {
                /**
                 * Worker did not respond to heartbeats. No need to send destroy
                 * message, because it is not going to respond anyway.
                 *
                 * So lets just make the worker invalid for all the actions,
                 * and remove it from the manager.
                 */
                $worker->setValid(false);

                // Worker crashed, add the request in front of the queue.
                $request = $worker->getRequest();
                if ($request !== null) {
                    if ($request->retry() > 2) {
                        $this->getLogger()->info(
                            'To many retries for: '.$request->getActionName().' ('.$request->getId().').'
                        );
                        $this->storage[$request->getId()] = 'STATUS:500:Poison';
                    } else {
                        array_unshift($this->clientRequests, $request);
                    }
                }
                unset($this->workers[$id]);
                continue;
            }

            if ($worker->isReady() && $heartbeatAt > $worker->getActivityAt()) {
                // Worker is idle, send a heartbeat.
                $this->reply($worker, new HeartbeatResponseWorkerhandler());
            }
        }
    }

    /**
     * Set the Logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the Logger.
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
