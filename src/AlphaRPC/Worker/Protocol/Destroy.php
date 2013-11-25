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

class Destroy extends MessageAbstract
{
    /**
     * Creates a Destroy message from the Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return Destroy
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
