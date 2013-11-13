<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
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
class ClientHandlerJobResponse extends MessageAbstract
{
    /**
     * Contains the Request ID that is assigned by the ClientHandler.
     *
     * @var string
     */
    protected $requestId;

    /**
     * Contains WorkerHandler that will handle this request..
     *
     * @var string
     */
    protected $workerHandlerId;

    /**
     *
     * @param string $requestId
     * @param string $workerHandlerId
     */
    public function __construct($requestId, $workerHandlerId)
    {
        $this->requestId = $requestId;
        $this->workerHandlerId = $workerHandlerId;
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
     * Returns the WorkerHandler ID.
     *
     * @return string
     */
    public function getWorkerHandlerId()
    {
        return $this->workerHandlerId;
    }

    /**
     * Creates an instance message from the Message.
     *
     * @param Message $msg
     *
     * @return ClientHandlerJobResponse
     */
    public static function fromMessage(Message $msg)
    {
        return new self($msg->shift(), $msg->shift());
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        return new Message(array(
            $this->getRequestId(),
            $this->getWorkerHandlerId(),
        ));
    }
}
