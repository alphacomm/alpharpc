<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Timer;

use AlphaRPC\Exception\InvalidArgumentException;

class TimeoutTimer implements TimerInterface
{
    /**
     *
     * @var ProviderInterface
     */
    protected $timeProvider;

    /**
     *
     * @var float
     */
    protected $endTime;

    /**
     * Create a Timer that defines the timeout in microseconds.
     *
     * @param int               $timeout
     * @param ProviderInterface $timeProvider
     *
     * @throws InvalidArgumentException
     */
    public function __construct($timeout, ProviderInterface $timeProvider = null)
    {
        $timeProvider = $timeProvider ?: new SystemTimeProvider();
        if ($timeout < 0) {
            throw new InvalidArgumentException('Timeout must not be less than zero.');
        }
        $this->timeProvider = $timeProvider;
        $this->endTime = $timeProvider->getMicrotime()+($timeout/1000);
    }

    /**
     * Timeout in microseconds.
     *
     * @return int
     */
    public function timeout()
    {
        $timeout = ($this->endTime - $this->timeProvider->getMicrotime())*1000;
        if ($timeout < 0) {
            return 0;
        }

        return $timeout;
    }

    /**
     * Is the timeout expired?
     *
     * @return boolean
     */
    public function isExpired()
    {
        if ($this->endTime > $this->timeProvider->getMicrotime()) {
            return false;
        }

        return true;
    }
}
