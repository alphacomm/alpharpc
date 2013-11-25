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
 * The request timed out.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class TimeoutResponse extends MessageAbstract
{
    /**
     *
     * @var string
     */
    protected $requestId = null;

    /**
     *
     * @param string $requestId
     */
    public function __construct($requestId = null)
    {
        $this->requestId = $requestId;
    }

    /**
     *
     * @return int
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
     * @return TimeoutResponse
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
