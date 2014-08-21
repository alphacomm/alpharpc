<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Socket;

use AlphaRPC\Common\MessageStream\MessageEvent;
use AlphaRPC\Common\MessageStream\StreamInterface;
use AlphaRPC\Common\Protocol\MessageFactory;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\TimeoutException;
use AlphaRPC\Common\Timer\TimeoutTimer;
use AlphaRPC\Common\Timer\TimerInterface;
use AlphaRPC\Common\Timer\UnlimitedTimer;
use RuntimeException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Stream implements StreamInterface
{
    /**
     *
     * @var Socket
     */
    protected $socket;

    /**
     *
     * @var EventDispatcher
     */
    protected $eventDispatcher = null;

    /**
     *
     * @var TimerInterface
     */
    protected $defaultTimer;

    /**
     * @param Socket         $socket
     * @param TimerInterface $defaultTimer Optional
     */
    public function __construct(Socket $socket, TimerInterface $defaultTimer = null)
    {
        $this->socket = $socket;
        $this->defaultTimer = $defaultTimer ?: new UnlimitedTimer();
    }

    /**
     *
     * @param MessageInterface $message
     * @param string|null      $destination
     *
     * @return null
     */
    public function send(MessageInterface $message, $destination = null)
    {
        $msg_to_send = $message->toMessage();
        $msg_to_send->prepend(array(
            MessageFactory::getLatestProtocolVersion(),
            $message->getType()
        ));

        if ($destination !== null) {
            $msg_to_send->prependRoutingInformation(array($destination));
        }
        $this->socket->msend($msg_to_send);
    }

    /**
     *
     * @param TimerInterface $timer
     *
     * @return MessageInterface
     *
     * @throws TimeoutException
     */
    public function read(TimerInterface $timer = null)
    {
        $timer = $timer ?: $this->defaultTimer;
        $timeout = $timer->timeout();
        if (!$this->socket->hasEvents($timeout)) {
            throw new TimeoutException('Timeout: '.$timeout.'.');
        }

        // Read the raw message.
        $msg = $this->socket->mrecv();

        // Dispatch the event.
        $event = $this->createEvent($msg);
        $this->dispatch(StreamInterface::MESSAGE, $event);

        // Dispatch the new message.
        return $event->getProtocolMessage();
    }

    /**
     * Handles messages that are already in the buffer.
     *
     * When the timer expires, no more messages will be read, even
     * when there are still more in the buffer.
     *
     * @param TimerInterface $timer
     *
     * @return void
     * @throws \RuntimeException
     */
    public function handle(TimerInterface $timer = null)
    {
        $timer = $timer ?: new UnlimitedTimer();
        while (true) {
            try {
                // Read from the buffer, but don't wait for new messages.
                $this->read(new TimeoutTimer(0));
            } catch (TimeoutException $ex) {
                unset($ex);

                // There is no message in the buffer. Return immediately.
                return;
            }

            if ($timer->isExpired()) {
                // There is no more time to handle messages. Return immediately.
                return;
            }
        }
    }

    /**
     *
     * @param Message $msg
     *
     * @return MessageEvent
     */
    protected function createEvent(Message $msg)
    {
        return new MessageEvent($msg, $this->socket);
    }

    /**
     * @todo Use trait for this once drop php 5.3 support
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected function getEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function addListener($eventType, $callable, $priority = 0)
    {
        return $this->getEventDispatcher()->addListener($eventType, $callable, $priority);
    }

    public function dispatch($eventName, Event $event = null)
    {
        return $this->getEventDispatcher()->dispatch($eventName, $event);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->getEventDispatcher()->addSubscriber($subscriber);
    }

    public function getListeners($eventName = null)
    {
        return $this->getEventDispatcher()->getListeners($eventName);
    }

    public function hasListeners($eventName = null)
    {
        return $this->getEventDispatcher()->hasListeners($eventName);
    }

    public function removeListener($eventName, $listener)
    {
        return $this->getEventDispatcher()->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->getEventDispatcher()->removeSubscriber($subscriber);
    }
}
