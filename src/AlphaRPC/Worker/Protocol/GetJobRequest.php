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

/**
 * Request a new Job from the WorkerHandler.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class GetJobRequest extends MessageAbstract
{
    /**
     * Creates a FetchAction message from the Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return StorageStatus
     */
    public static function fromMessage(Message $msg)
    {
        return new self();
    }

    /**
     * Create a new Message from this FetchActions.
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function toMessage()
    {
        return new Message();
    }
}
