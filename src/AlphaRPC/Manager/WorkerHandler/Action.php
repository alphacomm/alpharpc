<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ManagerCommon
 */

namespace AlphaRPC\Manager\WorkerHandler;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage WorkerHandler
 */
class Action
{
    /**
     *
     * @var string
     */
    protected $name = null;

    /**
     * An array of workers that are available for this action.
     *
     * @var Worker[]
     */
    protected $workerList = array();

    /**
     * List of workers that should be ready to retrieve jobs.
     *
     * @var string[]
     */
    protected $waitingList = array();

    /**
     * Is set to true when "waitingWorkers" has at least one entry. Note that
     * this doesn't mean any worker is ready.
     *
     * @var boolean
     */
    protected $ready = false;

    /**
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a worker to the action.
     *
     * @param Worker $worker
     *
     * @return Action
     * @throws \Exception
     */
    public function addWorker($worker)
    {
        if (!$worker instanceof Worker) {
            throw new \Exception('$worker is not an instance of Worker');
        }
        $this->workerList[$worker->getId()] = $worker;

        return $this;
    }

    /**
     * Removes the worker from the action.
     *
     * @param Worker $id
     *
     * @return Action
     */
    public function removeWorker($id)
    {
        if ($id instanceof Worker) {
            $id = $id->getId();
        }

        if (isset($this->waitingList[$id])) {
            unset($this->waitingList[$id]);
        }
        if (isset($this->workerList[$id])) {
            unset($this->workerList[$id]);
        }

        return $this;
    }

    /**
     * Adds the worker to the waiting list if it is not already in there.
     *
     * @param string|Worker $id
     *
     * @return Action
     * @throws \Exception
     */
    public function addWaitingWorker($id)
    {
        if ($id instanceof Worker) {
            $id = $id->getId();
        }

        if (!isset($this->workerList[$id])) {
            throw new \Exception('Trying to add non existent worker to the queue.');
        }
        $worker = $this->workerList[$id];

        $this->ready = true;

        /*
         * Only add the worker to the end of the wait list if it is not
         * already there.
         */
        if (!isset($this->waitingList[$id])) {
            $this->waitingList[$id] = $worker;
        }

        return $this;
    }

    /**
     * Gets the first available worker in the waiting list.
     *
     * @return Worker|null
     */
    public function fetchWaitingWorker()
    {
        $count = count($this->waitingList);
        for ($i = 0; $i < $count; $i++) {
            $worker = array_shift($this->waitingList);
            if ($worker->isValid() && $worker->isReady()) {
                return $worker;
            }
        }

        $this->ready = false;

        return null;
    }

    /**
     *
     * @return int
     */
    public function isReady()
    {
        return $this->ready;
    }

    /**
     * Counts all available workers.
     *
     * Note that these include workers that are still processing requests.
     *
     * @return int
     */
    public function countWorkers()
    {
        return count($this->workerList);
    }

    /**
     * Removes all invalid workers.
     *
     * @return Action
     */
    public function cleanup()
    {
        foreach ($this->workerList as $worker) {
            if (!$worker->isValid()) {
                $this->removeWorker($worker);
            }
        }

        return $this;
    }
}
