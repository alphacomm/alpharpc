<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Worker\Protocol;

use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

class ExecuteJobResponse extends MessageAbstract
{
    protected $requestId;

    protected $result;

    /**
     * Create a new StorageError message.
     */
    public function __construct($requestId, $result)
    {
        $this->requestId = (string) $requestId;
        $this->result = (string) $result;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getResult()
    {
        return $this->result;
    }

    /**
     * Creates a FetchAction message from the Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return StorageStatus
     */
    public static function fromMessage(Message $msg)
    {
        return new static($msg->shift(), $msg->shift());
    }

    /**
     * Create a new Message from this FetchActions.
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function toMessage()
    {
        return new Message(array(
            $this->requestId,
            $this->result
        ));
    }
}
