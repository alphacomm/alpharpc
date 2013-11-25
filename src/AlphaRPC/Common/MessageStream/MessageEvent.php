<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\MessageStream;

use AlphaRPC\Common\Protocol\MessageFactory;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Socket\Message;
use AlphaRPC\Common\Socket\Socket;
use Symfony\Component\EventDispatcher\Event;

class MessageEvent extends Event
{
    /**
     *
     * @var Message
     */
    protected $message;

    /**
     *
     * @var Socket
     */
    protected $socket;

    /**
     *
     * @var MessageInterface
     */
    protected $protocolMessage;

    /**
     *
     * @param \AlphaRPC\Common\Socket\Message $message
     * @param \AlphaRPC\Common\Socket\Socket  $socket
     */
    public function __construct(Message $message, Socket $socket)
    {
        $this->message = $message;
        $this->socket = $socket;
        if (MessageFactory::isProtocolMessage($message)) {
            $this->protocolMessage = MessageFactory::createProtocolMessage(clone $message);
        }
    }

    /**
     *
     * @return Socket
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     *
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @return MessageInterface
     */
    public function getProtocolMessage()
    {
        return $this->protocolMessage;
    }
}
