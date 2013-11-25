<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\MessageStream;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractStream implements StreamInterface
{
    /**
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dispatcher->addSubscriber($subscriber);
    }

    public function dispatch($eventName, Event $event = null)
    {
        return $this->dispatcher->dispatch($eventName, $event);
    }

    public function getListeners($eventName = null)
    {
        return $this->getListeners($eventName);
    }

    public function hasListeners($eventName = null)
    {
        return $this->hasListeners($eventName);
    }

    public function removeListener($eventName, $listener)
    {
        return $this->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->removeSubscriber($subscriber);
    }
}
