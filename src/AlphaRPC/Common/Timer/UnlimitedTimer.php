<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Timer;

class UnlimitedTimer implements TimerInterface
{
    /**
     * Always returns -1, because we can wait forever.
     *
     * @return int
     */
    public function timeout()
    {
        return -1;
    }

    /**
     * Never expires.
     *
     * @return boolean
     */
    public function isExpired()
    {
        return false;
    }
}
