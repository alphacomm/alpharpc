<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Timer;

interface TimerInterface
{
    /**
     * Return the timeout in MS, returns -1 if the timeout is allowed to be
     * unlimited.
     *
     * @return int
     */
    public function timeout();

    /**
     * Returns true if the timer has expired.
     *
     * @return boolean
     */
    public function isExpired();
}
