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
class QueueStatusRequest extends MessageAbstract
{
    /**
     * Creates an instance based on the given message.
     *
     * @param Message $msg
     *
     * @return QueueStatusRequest
     */
    public static function fromMessage(Message $msg)
    {
        return new self();
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        return new Message(array());
    }
}
