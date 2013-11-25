<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage MessageTypes
 */

namespace AlphaRPC\Manager\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

/**
 * ClientHandlerJobRequest is the Message that the ClientHandler uses
 * to request the WorkerHandler to process a Job
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage MessageTypes
 */
class ClientHandlerJobRequest extends MessageAbstract
{
    /**
     * Contains the Parameters of a Request.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Contains the Request ID that is assigned by the ClientHandler.
     *
     * @var string
     */
    protected $requestId;

    /**
     * Contains the action that should be invoked.
     *
     * @var string
     */
    protected $actionName;

    public function __construct($requestId, $actionName, array $parameters)
    {
        $this->requestId = $requestId;
        $this->actionName = $actionName;
        $this->parameters = $parameters;
    }

    /**
     * Returns the Request Parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the Request ID.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Returns the action.
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Creates an instance from the Message.
     *
     * @param Message $msg
     *
     * @return ClientHandlerJobRequest
     */
    public static function fromMessage(Message $msg)
    {
        $requestId = $msg->shift();
        $actionName = $msg->shift();
        $parameters = $msg->toArray();

        return new self($requestId, $actionName, $parameters);
    }

    /**
     * Create a new Message from this StorageStatus.
     *
     * @return Message
     */
    public function toMessage()
    {
        $m = new Message(array(
            $this->getRequestId(),
            $this->getActionName(),
        ));
        $m->append($this->getParameters());

        return $m;
    }
}
