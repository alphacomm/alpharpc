<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\MessageStream;

use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\TimeoutException;
use AlphaRPC\Common\Timer\TimerInterface;
use AlphaRPC\Common\Timer\UnlimitedTimer;
use RuntimeException;

class ArrayStream extends AbstractStream
{
    /**
     *
     * @var MessageInterface[]
     */
    private $read = array();

    /**
     *
     * @var MessageInterface[]
     */
    private $send = array();

    /**
     *
     * @param TimerInterface $timer
     *
     * @return void
     * @throws RuntimeException
     */
    public function handle(TimerInterface $timer = null)
    {
        $timer = $timer ?: new UnlimitedTimer();
        do {
            try {
                $this->read(new TimeoutTimer(0));
            } catch (TimeoutException $ex) {
                // Timeout is not relevant here.
                unset($ex);

                return;
            }
        } while (!$timer->isExpired());
    }

    /**
     *
     * @param TimerInterface $timer
     */
    public function read(TimerInterface $timer = null)
    {
        $message = array_shift($this->read);
        if ($message === null) {
            throw new TimeoutException('No more messages in the queue.');
        }

        return $message;
    }

    /**
     *
     * @param MessageInterface $msg
     */
    public function send(MessageInterface $msg)
    {
        $this->send[] = $msg;
    }

    /**
     *
     * @return MessageInterface[]
     */
    public function getSendList()
    {
        return $this->send;
    }

    /**
     *
     * @param MessageInterface
     */
    public function addMessageToRead(MessageInterface $msg)
    {
        $this->read[] = $msg;
    }
}
