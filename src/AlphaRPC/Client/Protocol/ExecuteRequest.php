<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Client\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

/**
 * Client request to execute a job.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class ExecuteRequest extends MessageAbstract
{
    /**
     *
     * @var string
     */
    protected $requestId;

    /**
     *
     * @var string
     */
    protected $action;

    /**
     *
     * @var array
     */
    protected $params;

    /**
     *
     * @param string $requestId
     * @param string $action
     * @param array  $params
     */
    public function __construct($requestId, $action, array $params)
    {
        $this->requestId = $requestId;
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * The request id.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * The action to execute.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * The parameters for this action.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Creates an instance of this class based on the Message.
     *
     * @param Message $msg
     *
     * @return ExecuteRequest
     */
    public static function fromMessage(Message $msg)
    {
        return new self($msg->shift(), $msg->shift(), $msg->toArray());
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        $msg = new Message(array($this->requestId, $this->action));

        return $msg->append($this->params);
    }
}
