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
 * Returns the response of a request.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class FetchResponse extends MessageAbstract
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
    protected $result;

    /**
     *
     * @param string $requestId
     * @param string $result
     */
    public function __construct($requestId, $result)
    {
        $this->requestId = $requestId;
        $this->result = $result;
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
     * The serialized result of the job.
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Creates an instance of this class based on the Message.
     *
     * @param Message $msg
     *
     * @return FetchResponse
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
        return new Message(array($this->requestId, $this->result));
    }
}
