<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Timer;

class SystemTimeProvider implements ProviderInterface
{
    /**
     * Get the microtime of the system.
     *
     * @return float
     */
    public function getMicrotime()
    {
        return microtime(true);
    }

    /**
     * Get the time of the system.
     *
     * @return int
     */
    public function getTime()
    {
        return time();
    }
}
