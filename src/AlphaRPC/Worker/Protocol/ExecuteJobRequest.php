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

class ExecuteJobRequest extends MessageAbstract
{
    protected $requestId;

    protected $action;

    protected $params;

    /**
     * Create a new StorageError message.
     */
    public function __construct($requestId, $action, array $params)
    {
        $this->requestId = (string) $requestId;
        $this->action = (string) $action;
        $this->params = $params;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParams()
    {
        return $this->params;
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
        return new static($msg->shift(), $msg->shift(), $msg->toArray());
    }

    /**
     * Create a new Message from this FetchActions.
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function toMessage()
    {
        $msg = new Message(array(
                $this->requestId,
                $this->action,
            ));

        $msg->append($this->params);

        return $msg;
    }
}
