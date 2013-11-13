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
 * Fetch the result for a request.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class FetchRequest extends MessageAbstract
{
    /**
     *
     * @var string
     */
    protected $requestId;

    /**
     *
     * @var boolean
     */
    protected $waitForResult;

    /**
     *
     * @param string  $requestId
     * @param boolean $waitForResult
     */
    public function __construct($requestId, $waitForResult)
    {
        $this->requestId = $requestId;
        $this->waitForResult = (boolean) $waitForResult;
    }

    /**
     * Get the requestId.
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Do we need to wait for the job to reach a result?
     *
     * @return boolean
     */
    public function getWaitForResult()
    {
        return $this->waitForResult;
    }

    /**
     * Creates an instance of this class based on the Message.
     *
     * @param Message $msg
     *
     * @return FetchRequest
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
        return new Message(array($this->requestId, $this->waitForResult));
    }
}
