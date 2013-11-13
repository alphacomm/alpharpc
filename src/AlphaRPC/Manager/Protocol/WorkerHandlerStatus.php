<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Manager\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

class WorkerHandlerStatus extends MessageAbstract
{
    /**
     *
     * @var string
     */
    protected $workerHandlerId;

    /**
     *
     * @var string
     */
    protected $requestId;

    /**
     *
     * @param string $id
     */
    public function __construct($id, $requestId = null)
    {
        $this->workerHandlerId = $id;
        $this->requestId = $requestId;
    }

    /**
     * The worker handler id.
     *
     * @return string
     */
    public function getWorkerHandlerId()
    {
        return $this->workerHandlerId;
    }

    /**
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Creates a message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        return new Message(array($this->workerHandlerId, $this->requestId));
    }

    /**
     * Creates an instance based on the Message.
     *
     * @param Message $msg
     *
     * @return WorkerHandlerStatus
     */
    public static function fromMessage(Message $msg)
    {
        $workerHandlerId = $msg->shift();
        $requestId = $msg->shift();
        if (!$requestId) {
            $requestId = null;
        }

        return new self($workerHandlerId, $requestId);
    }
}
