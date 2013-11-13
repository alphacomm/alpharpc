<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */

namespace AlphaRPC\Manager\ClientHandler;

/**
 * This is the ClientHandler representation of a Client.
 *
 * It contains the current (and previous) request.
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */
class Client
{
    /**
     *
     * @var ClientBucket
     */
    protected $bucket;

    /**
     * Id for the client
     *
     * @var string
     */
    protected $id;

    /**
     * Last activity in seconds including microtime.
     *
     * @var float
     */
    protected $time;

    /**
     * The id of the request the client is interested in.
     *
     * @var string
     */
    protected $request = null;

    /**
     * The id of the previous request the client was interested in.
     *
     * @var string
     */
    protected $previousRequest = null;

    /**
     * Is the client willing to wait for the worker to execute the job.
     *
     * When true the client will block untill a result is given.
     *
     * @var boolean
     */
    protected $waitForResult;

   /**
    * Creates a new Client instance.
    *
    * @param \AlphaRPC\Manager\ClientHandler\ClientBucket $bucket
    * @param string $id
    */
    public function __construct(ClientBucket $bucket, $id)
    {
        $this->bucket = $bucket;
        $this->id = $id;
        $this->time = microtime(true);
        $this->bucket->refresh($this);
    }

    /**
     * Id for the client
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Last activity in seconds including microseconds.
     *
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * The id of the Request the client is interested in.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * The id of the previous Request the client was interested in.
     *
     * @return string
     */
    public function getPreviousRequest()
    {
        return $this->previousRequest;
    }

    /**
     * Is the client willing to wait for the worker to execute the job.
     *
     * When true the client will block untill a result is given.
     *
     * @return boolean
     */
    public function getWaitForResult()
    {
        return $this->waitForResult;
    }

    /**
     * Sets the request the client is interested in.
     *
     * @param string  $request
     * @param boolean $waitForResult
     *
     * @return \AlphaRPC\Manager\ClientHandler\Client
     */
    public function setRequest($request, $waitForResult = true)
    {
        $this->previousRequest = $this->request;
        $this->request = $request;
        $this->waitForResult = $waitForResult;
        $this->bucket->refresh($this);

        return $this;
    }
}
