<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Worker\Protocol;

use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

class ActionNotFound extends MessageAbstract
{
    /**
     * The non-existing action.
     *
     * @var string
     */
    protected $action;

    /**
     * Contains the Request ID for which no action was found.
     *
     * @var string
     */
    protected $requestId;

    /**
     * Create a new StorageError message.
     */
    public function __construct($requestId, $action)
    {
        $this->action = $action;
        $this->requestId = $requestId;
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
        return new self($msg->shift(), $msg->shift());
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
            $this->action,
        ));
    }

    /**
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
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
