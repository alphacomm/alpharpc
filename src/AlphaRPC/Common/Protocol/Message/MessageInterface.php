<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
namespace AlphaRPC\Common\Protocol\Message;

use AlphaRPC\Common\Socket\Message;

interface MessageInterface
{
    /**
     * Creates a MessageType from the given Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return MessageInterface
     */
    public static function fromMessage(Message $msg);

    /**
     * Creates a Message object.
     *
     * @return Message
     */
    public function toMessage();

    /**
     * Returns the Message Type.
     *
     * @return integer
     */
    public function getType();
}
