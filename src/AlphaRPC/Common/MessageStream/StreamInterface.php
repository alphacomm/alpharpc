<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */

namespace AlphaRPC\Common\MessageStream;

use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Timer\TimerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
interface StreamInterface extends EventDispatcherInterface
{
    const MESSAGE = 'zm.message';

    /**
     * Handle the given number of messages.
     *
     * @param TimerInterface $timeout
     *
     * @return null
     *
     * @throws \RuntimeException
     */
    public function handle(TimerInterface $timer = null);

    /**
     *
     *
     * @param TimerInterface $timeout
     *
     * @return MessageInterface
     */
    public function read(TimerInterface $timer = null);

    /**
     * Send a Message.
     *
     * @param MessageInterface $msg
     *
     * @return void
     * @throws RuntimeException
     */
    public function send(MessageInterface $msg);
}
