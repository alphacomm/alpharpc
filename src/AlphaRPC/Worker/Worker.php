<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Worker
 */

namespace AlphaRPC\Worker;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Worker
 */
class Worker implements LoggerAwareInterface
{
    const INIT             = 'init';
    const REGISTER         = 'register';
    const READY            = 'ready';
    const BUSY             = 'busy';
    const RESULT_AVAILABLE = 'result-available';
    const RESULT           = 'result';
    const SHUTDOWN         = 'shutdown';
    const INVALID          = 'invalid';

    /**
     * Contains the list of all actions provided by this Worker.
     *
     * @var array
     */
    protected $actions = array();

    /**
     * The ID of the Request that is currently being handled.
     *
     * @var string
     */
    protected $requestId = null;

    /**
     * The result of the current Request.
     *
     * @var string
     */
    protected $result = null;

    /**
     * Contains the timestamp of the latest activity.
     *
     * @var float
     */
    protected $activityAt;

    /**
     *
     * @var string
     */
    protected $state;

    /**
     * Is the service process still up?
     *
     * @var boolean
     */
    protected $serviceRunning = true;

    /**
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    public function __construct()
    {
        $this->touch();
        $this->setState(self::INIT);
    }

    /**
     * Set the current state of this Worker.
     *
     * @param integer $state
     *
     * @return ZMWorker
     */
    public function setState($state)
    {
        $this->getLogger()->debug('Changing state from: '.$this->getState().' to: '.$state.'.');
        $this->state = $state;

        return $this;
    }

    /**
     * Returns the current state of this Worker.
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the actions that this worker can handle.
     *
     * @param array $actions
     *
     * @return \AlphaRPC\Worker\Worker
     * @throws \RuntimeException
     */
    public function setActions($actions)
    {
        if ($this->getState() != self::INIT) {
            throw new \RuntimeException('Invalid state.');
        }

        if (count($actions) == 0) {
            $this->setState(self::INVALID);
            throw new \RuntimeException('No actions given.');
        }

        $this->actions = array_combine($actions, $actions);
        $this->setState(self::REGISTER);

        return $this;
    }

    /**
     *
     * @param string $action
     *
     * @return boolean
     */
    public function hasAction($action)
    {
        return isset($this->actions[$action]);
    }

    /**
     * Returns the list actions that this Worker will handle.
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Returns the time of the latest activity.
     *
     * @return float
     */
    public function getActivityAt()
    {
        return $this->activityAt;
    }

    /**
     *
     * @return \AlphaRPC\Worker\Worker
     */
    public function touch()
    {
        $this->activityAt = microtime(true);

        return $this;
    }

    /**
     *
     * @param int $timeout
     *
     * @return boolean
     */
    public function isExpired($timeout)
    {
        $expiryAt = microtime(true) - ($timeout / 1000);
        if ($this->getActivityAt() < $expiryAt) {
            return true;
        }

        return false;
    }

    /**
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set the result for the currently handled request.
     *
     * @param string $requestId
     * @param mixed  $result
     */
    public function setResult($requestId, $result)
    {
        $this->requestId = $requestId;
        $this->result    = $result;
        $this->setState(self::RESULT_AVAILABLE);
    }

    /**
     * Returns the ID of the Request that is currently being handled.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Reset this worker to get ready for another Request.
     */
    public function cleanup()
    {
        $this->requestId = null;
        $this->result    = null;
        $this->setState(self::READY);
    }

    /**
     * Sets the worker state to shutdown.
     *
     * @return null
     */
    public function shutdown()
    {
        $this->setState(self::SHUTDOWN);
    }

    /**
     * Let the worker know the service is down.
     *
     * @return null
     */
    public function onServiceDown()
    {
        if (!$this->serviceRunning) {
            return;
        }

        $this->getLogger()->debug('Service is no longer alive.');
        $this->serviceRunning = false;
        $this->shutdown();
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
