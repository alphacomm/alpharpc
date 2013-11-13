<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
namespace AlphaRPC\Common\Protocol\Message;

use AlphaRPC\Common\Socket\Message;

/**
 * This Message contains a special error message indicating
 * that the Protocol version is unknown.
 *
 * @package AlphaRPC
 * @subpackage Common
 */
class UnknownProtocolVersion extends ErrorMessageAbstract
{
    /**
     * Creates a MessageType from the given Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return MessageInterface
     */
    public static function fromMessage(Message $msg)
    {
        return new self();
    }

    /**
     * Creates a Message object.
     *
     * @return Message
     */
    public function toMessage()
    {
        // Force the Version to Version 1, so it will be
        // compatible with all other versions.
        return new Message(array(
            1,
            $this->getType(),
        ));
    }
}
