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
class Worker
{
    /**
     * Id used for routing messages.
     * @var string
     */
    protected $id;

    /**
     * Is the worker ready to receive a reply?
     * @var boolean
     */
    protected $ready = false;

    /**
     * List of actions the worker listens to.
     * @var array
     */
    protected $actionList = array();

    /**
     * Calulated value if the worker is valid.
     * @var boolean
     */
    protected $valid = false;

    /**
     * The microtime when the worker expires
     * @var float
     */
    protected $activityAt = null;

    /**
     * The request the Worker is executing.
     * @var Request
     */
    protected $request = null;

    /**
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
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
     * Returns the ID in hexadecimal form.
     *
     * @return string
     */
    public function getHexId()
    {
        return bin2hex($this->id);
    }

    /**
     *
     * @param boolean $ready
     *
     * @return Worker
     */
    public function setReady($ready = true)
    {
        $this->ready = $ready;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isReady()
    {
        return $this->ready;
    }

    /**
     *
     * @return int
     */
    public function getActionCount()
    {
        return count($this->actionList);
    }

    /**
     * Adds a action name to the list of actions the worker listens to.
     *
     * @param Action $action
     *
     * @return Worker
     */
    public function addAction($action)
    {
        if ($action instanceof Action) {
            $action = $action->getName();
        }
        $this->actionList[] = $action;
        $this->valid = null;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getActionList()
    {
        return $this->actionList;
    }

    /**
     *
     * @return Worker
     */
    public function clearActionList()
    {
        $this->actionList = array();
        $this->valid = null;

        return $this;
    }

    /**
     *
     * @param boolean $valid
     *
     * @return Worker
     */
    public function setValid($valid)
    {
        $this->valid = (bool) $valid;

        return $this;
    }

    /**
     * Calculates if the worker is valid.
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Set the time of the last activity to the Worker.
     *
     * @return Worker
     */
    public function touch()
    {
        $this->activityAt = microtime(true);

        return $this;
    }

    /**
     * Return the time of the last activity.
     *
     * @return float
     */
    public function getActivityAt()
    {
        return $this->activityAt;
    }

    /**
     * Set the Request that this Worker is currently working on.
     *
     * @param Request $request
     *
     * @return Worker
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Return the currently handled Request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
