<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ManagerCommon
 */

namespace AlphaRPC\Manager;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ManagerCommon
 */
class Request
{
    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $actionName;

    /**
     * Contains the request parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Contains the time of the last activity.
     *
     * @var float
     */
    protected $activityAt = 0.0;

    /**
     * Contains the ID of the WorkerHandler
     * that is processing the request.
     *
     * @var string
     */
    protected $workerHandlerId = null;

    /**
     *
     * @var int
     */
    protected $retries = 0;

    /**
     *
     * @param string $id
     * @param string $actionName
     * @param array  $params
     */
    public function __construct($id, $actionName, $params)
    {
        $this->id = $id;
        $this->actionName = $actionName;
        $this->params = $params;

        $this->touch();
    }

    public function touch()
    {
        $this->activityAt = microtime(true);

        return $this;
    }

    public function getActivityAt()
    {
        return $this->activityAt;
    }

    /**
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Returns the parameters for the Request.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the Id of the WorkerHandler that
     * is processing this request.
     *
     * @param string $workerHandlerId
     */
    public function setWorkerHandlerId($workerHandlerId)
    {
        $this->workerHandlerId = $workerHandlerId;
    }

    /**
     * Returns the Id of the WorkerHandler that
     * is processing this request.
     *
     * @return string
     */
    public function getWorkerHandlerId()
    {
        return $this->workerHandlerId;
    }

    /**
     * @param $worker
     *
     * @return $this
     *
     * @deprecated Since version 1.3. Use ::setWorkerHandlerId() instead.
     */
    public function setWorker($worker)
    {
        $this->setWorkerHandlerId($worker);

        return $this;
    }

    /**
     * @return string
     *
     * @deprecated Since version 1.3. Use ::getWorkerHandlerId() instead.
     */
    public function getWorker()
    {
        return $this->getWorkerHandlerId();
    }

    public function retry()
    {
        $this->retries++;

        return $this->retries;
    }
}
