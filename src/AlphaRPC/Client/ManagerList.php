<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Client;

class ManagerList
{
    /**
     * Flags for managing priority.
     */
    const FLAG_AVAILABLE = 'available';
    const FLAG_UNAVAILABLE = 'unavailable';

    /**
     * Complete List of managers.
     *
     * @var array
     */
    private $managerList = array();

    /**
     * Managers listed by availability.
     *
     * @var array
     */
    private $managerStatus = array();

    /**
     * Keeps track of the amount of ::getPrioritized calls.
     *
     * @var int
     */
    private $resetCounter;

    /**
     * When the "resetCounter" reaches the value of "resetAt", all managers are considered available.
     *
     * Long running clients will keep distributing load.
     *
     * @var int
     */
    private $resetAt;

    /**
     * @param int $unavailableCheckAt
     */
    public function __construct($unavailableCheckAt = 100)
    {
        $this->managerStatus = array(
            self::FLAG_AVAILABLE => array(),
            self::FLAG_UNAVAILABLE => array(),
        );

        $this->resetAt = $unavailableCheckAt;
        $this->resetCounter = 0;
    }

    /**
     * Adds a manager dsn to the list.
     *
     * @param string $manager
     *
     * @return ManagerList
     * @throws \InvalidArgumentException
     */
    public function add($manager)
    {
        if (!is_string($manager)) {
            throw new \InvalidArgumentException('ManagerList::add requires $manager to be a string.');
        }

        $this->managerList[$manager] = $manager;
        $this->managerStatus[self::FLAG_AVAILABLE][$manager] = $manager;

        return $this;
    }

    /**
     * Returns an array or manager dsns, sorted by priority.
     *
     * Available managers get priority over unavailable managers.
     * Once every x calls all managers will be flagged as available.
     *
     * This makes sure managers that where unavailable for a period of
     * time will receive jobs once they get up.
     *
     * @return array[]
     */
    public function toPrioritizedArray()
    {
        // Reset the available managers every $this->unavailableCheckAt requests.
        if (($this->resetCounter % $this->resetAt) == 0) {
            $this->resetAvailableManagers();
        }
        $this->resetCounter++;

        $available =& $this->managerStatus[self::FLAG_AVAILABLE];
        $unavailable =& $this->managerStatus[self::FLAG_UNAVAILABLE];

        // Shuffle the available managers to distribute load.
        shuffle($available);

        // Add the unavailable managers at the end.
        $managerList = array_merge($available, $unavailable);

        return $managerList;
    }


    /**
     * Flags a manager as (un)available, changing its priority.
     *
     * @param string $manager
     * @param string $flag
     *
     * @throws \InvalidArgumentException
     */
    public function flag($manager, $flag)
    {
        if (!in_array($flag, array(self::FLAG_AVAILABLE, self::FLAG_UNAVAILABLE))) {
            throw new \InvalidArgumentException(
                'Client::flagManager $flag argument must be one of the FLAG_ constants');
        }

        if (!isset($this->managerStatus[$flag][$manager])) {
            $this->managerStatus[$flag][$manager] = $manager;
        }

        $removeFlag = ($flag != self::FLAG_AVAILABLE) ? self::FLAG_AVAILABLE : self::FLAG_UNAVAILABLE;
        if (isset($this->managerStatus[$removeFlag][$manager])) {
            unset($this->managerStatus[$removeFlag][$manager]);
        }
    }


    /**
     * Makes all managers available.
     */
    protected function resetAvailableManagers()
    {
        $this->managerStatus[self::FLAG_AVAILABLE] = $this->managerList;
        $this->managerStatus[self::FLAG_UNAVAILABLE] = array();
    }
} 