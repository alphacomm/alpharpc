<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Client\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

/**
 * The request is marked as poisoned.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class PoisonResponse extends MessageAbstract
{
    /**
     *
     * @var string
     */
    protected $requestId;

    /**
     *
     * @param string $requestId
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
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
     * Creates an instance of this class based on the Message.
     *
     * @param Message $msg
     *
     * @return PoisonResponse
     */
    public static function fromMessage(Message $msg)
    {
        return new self($msg->shift());
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        return new Message(array($this->requestId));
    }
}
