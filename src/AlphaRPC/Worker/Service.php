<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Worker
 */

namespace AlphaRPC\Worker;

use AlphaRPC\Common\MessageStream\MessageEvent;
use AlphaRPC\Common\MessageStream\StreamInterface;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Scheduler\Schedule;
use AlphaRPC\Common\Scheduler\Scheduler;
use AlphaRPC\Common\Socket\Socket;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZMQ;
use ZMQPoll;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Worker
 */
class Service implements LoggerAwareInterface
{
    /**
     * Contains the callbacks for the actions that can be handled.
     *
     * @var callback[]
     */
    protected $actions = array();

    /**
     * Contains the interface used to serialize requests and responses.
     *
     * @var \AlphaRPC\Common\Serialization\SerializerInterface
     */
    protected $serializer;

    /**
     * The logger
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     *
     * @var CallbackStream
     */
    protected $stream = null;

    /**
     *
     * @var ZMQPoll
     */
    protected $poll = null;

    /**
     * Whether this service is still running.
     *
     * @var boolean
     */
    protected $running = false;

    /**
     *
     * @var \AlphaRPC\Common\Scheduler\Scheduler
     */
    protected $scheduler = null;

    /**
     * @param Socket $socket
     */
    public function __construct(Socket $socket)
    {
        $this->createStream($socket);
    }

    /**
     *
     * @param Socket $socket
     *
     * @return Service
     * @throws RuntimeException
     */
    public function createStream(Socket $socket)
    {
        if ($socket->getSocketType() != ZMQ::SOCKET_REP) {
            throw new RuntimeException('Invalid socket type.');
        }

        $this->getPoller()->add($socket, ZMQ::POLL_IN);

        $stream = $socket->getStream();
        $callback = array($this, 'onWorkerMessage');
        $stream->addListener(StreamInterface::MESSAGE, function(MessageEvent $event) use ($callback) {
            call_user_func($callback, $event->getProtocolMessage());
        });
        $this->stream = $stream;

        return $this;
    }

    /**
     *
     * @param SerializerInterface $serializer
     *
     * @return Service
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        if ($this->serializer === null) {
            $this->serializer = new \AlphaRPC\Common\Serialization\PhpSerializer();
        }

        return $this->serializer;
    }

    /**
     * Add an action to this Worker.
     *
     * @param string   $action   The name of the action
     * @param callback $callback The action to perform when the action is requested.
     *
     * @return Service
     * @throws \Exception
     */
    public function addAction($action, $callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception('Callback is not callable.');
        }

        $this->getLogger()->info('[SERVICE] Registering: '.$action);

        $this->actions[$action] = $callback;

        return $this;
    }

    /**
     * Returns the list of available actions.
     *
     * @return array
     */
    public function getActionList()
    {
        return array_keys($this->actions);
    }

    /**
     * Indicates that this Service should be stopped.
     */
    public function stop($signal = null)
    {
        if (null !== $signal) {
            $this->getLogger()->info('[SERVICE] Stop called, because of signal #'.$signal.'.');
        } else {
            $this->getLogger()->info('[SERVICE] Stop called');
        }
        $this->running = false;
    }

    /**
     * Handles a message send by the worker.
     *
     * @param \AlphaRPC\Common\Protocol\Message\MessageInterface $msg
     *
     * @return null
     * @throws RuntimeException
     */
    public function onWorkerMessage(MessageInterface $msg)
    {
        if ($msg instanceof Protocol\ActionListRequest) {
            $this->getLogger()->debug('[SERVICE] Returning list of actions.');
            $this->stream->send(new Protocol\ActionListResponse($this->getActionList()));

            return;
        }

        if ($msg instanceof Protocol\ExecuteJobRequest) {
            $requestId = $msg->getRequestId();
            $action = $msg->getAction();

            $this->getLogger()->debug('[SERVICE] Job for action: '.$action.' with id: '.$requestId);

            $params = array();
            $serialized = $msg->getParams();
            foreach ($serialized as $param) {
                $params[] = $this->getSerializer()->unserialize($param);
            }

            if (!isset($this->actions[$action])) {
                $this->getLogger()->notice('[SERVICE] Action '.$action.' not found.');
                $this->stream->send(new Protocol\ActionNotFound($requestId, $action));

                return;
            }

            $return = $this->execute($action, $params);
            $sendReturn = $this->getSerializer()->serialize($return);

            $this->stream->send(new Protocol\ExecuteJobResponse($requestId, $sendReturn));

            return;
        }

        throw new RuntimeException('Invalid request.');
    }

    /**
     * Runs the action.
     *
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     */
    public function execute($action, $params)
    {
        return call_user_func_array($this->actions[$action], $params);
    }

    /**
     * Run and execute a service.
     */
    public function run()
    {
        pcntl_signal(SIGINT, array($this, 'stop'));
        pcntl_signal(SIGTERM, array($this, 'stop'));

        $this->running = true;
        while ($this->running) {
            $this->process();
        }
    }

    /**
     * Handle a single job and/or schedule. Unblocks at least after 60 seconds.
     *
     * @return null
     */
    public function process()
    {
        pcntl_signal_dispatch();

        try {
            $read = $write = array();
            $timeout = $this->calculatePollTimeout();
            $this->getPoller()->poll($read, $write, $timeout);
        } catch (\ZMQPollException $ex) {
            pcntl_signal_dispatch();
            $this->getLogger()->debug('[SERVICE] Received Poll Exception: '.$ex->getMessage().'('.$ex->getCode().')');

            return;
        }

        $this->stream->handle();
        $this->runSchedule();
    }

    /**
     *
     * @return Scheduler
     */
    public function getScheduler()
    {
        if ($this->scheduler === null) {
            $this->scheduler = new Scheduler();
        }

        return $this->scheduler;
    }

    /**
     *
     * @param string   $name
     * @param string   $expression
     * @param callable $task
     */
    public function schedule($name, $expression, $task)
    {
        $schedule = new Schedule(
            $name,
            $expression,
            $task
        );
        $this->getScheduler()->insert($schedule);
    }

    /**
     * When the first schedule is due, this function will run and reschedule it.
     *
     * @return null
     */
    public function runSchedule()
    {
        if ($this->getScheduler()->isEmpty()) {
            return;
        }

        if (!$this->getScheduler()->top()->isDue()) {
            // Nothing to do.
            return;
        }

        $schedule = $this->getScheduler()->extract();

        $this->getLogger()->debug('Running schedule '.$schedule->getName().' with expression: '.$schedule->getExpression().'.');
        $schedule->run();

        $this->getScheduler()->insert($schedule);
    }

    /**
     * Calculates the poll timeout based on the next schedule.
     *
     * If there are no schedules this wil return the maximum poll timeout
     * wich is 60 by default.
     *
     * @return int
     */
    protected function calculatePollTimeout()
    {
        if ($this->getScheduler()->isEmpty()) {
            return 60000;
        }

        $nextSchedule = $this->getScheduler()->top();
        $nextTime = $nextSchedule->getNextRunDate();

        $timeout = $nextTime-time();
        if ($timeout > 60) {
            $timeout = 60;
        }
        
        if ($timeout < 0) {
            return 0;
        }

        return $timeout*1000;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger
     *
     * @return AlphaRPC
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        if ($this->stream instanceof LoggerAwareInterface) {
            $this->stream->setLogger($this->getLogger());
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
}
