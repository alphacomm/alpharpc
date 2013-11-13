<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Timer;

interface ProviderInterface
{
    /**
     * Return the time in seconds as a float.
     *
     * @return float
     */
    public function getMicrotime();

    /**
     * Return the time in seconds.
     *
     * @return int
     */
    public function getTime();
}
