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

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Client
 */
class Request
{
    /**
     *
     * @var string
     */
    protected $manager = null;

    /**
     *
     * @var string
     */
    protected $function = null;

    /**
     *
     * @var array
     */
    protected $params = array();

    /**
     *
     * @var mixed
     */
    protected $response = null;

    /**
     *
     * @var boolean
     */
    protected $hasResponse = false;

    /**
     *
     * @var string
     */
    protected $requestId = null;

    /**
     *
     * @param string $function
     * @param array  $params
     */
    public function __construct($function, array $params = array())
    {
        $this->function = $function;
        $this->params = $params;
    }

    /**
     *
     * @param string $server
     *
     * @return \AlphaRPC\Client\Request
     */
    public function setManager($server)
    {
        $this->manager = $server;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     *
     * @param mixed $response
     *
     * @return \AlphaRPC\Client\Request
     */
    public function setResponse($response)
    {
        $this->response = $response;
        $this->hasResponse = true;

        return $this;
    }

    /**
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     *
     * @return boolean
     */
    public function hasResponse()
    {
        return $this->hasResponse;
    }

    /**
     *
     * @return string
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     *
     * @param string $requestId
     *
     * @return \AlphaRPC\Client\Request
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }
}
