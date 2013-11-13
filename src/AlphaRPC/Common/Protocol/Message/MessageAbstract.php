<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Jacob Kiers <jacob@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Common
 */
namespace AlphaRPC\Common\Protocol\Message;

use AlphaRPC\Common\Protocol\MessageFactory;
use AlphaRPC\Common\Socket\Message;

abstract class MessageAbstract implements MessageInterface
{
    /**
     * Create a Message with the given parts.
     *
     * @param array $parts
     *
     * @return Message
     */
    protected static function createMessage(array $parts = array())
    {
        return new Message($parts);
    }

    /**
     * Returns the Message Type.
     *
     * @return int
     */
    public function getType()
    {
        return MessageFactory::getTypeIdByClass(get_called_class());
    }
}
