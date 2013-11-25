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

namespace AlphaRPC\Common\Scheduler;

use SplMinHeap;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
class Scheduler extends SplMinHeap
{
    /**
     * Peek at the next schedule.
     *
     * @return \AlphaRPC\Common\Scheduler\Schedule
     */
    public function top()
    {
        return parent::top();
    }

    /**
     * Get the next schedule and remove it from the list.
     *
     * @return \AlphaRPC\Common\Scheduler\Schedule
     */
    public function extract()
    {
        return parent::extract();
    }

    /**
     * Add the schedule.
     *
     * @param \AlphaRPC\Common\Scheduler\Schedule $schedule
     *
     * @return null
     * @throws \RuntimeException
     */
    public function insert($schedule)
    {
        if (!$schedule instanceof Schedule) {
            throw new \RuntimeException('Scheduler only accept Schedule instances.');
        }
        parent::insert($schedule);
    }

    /**
     * Compare the schedules, the one with the first nextRunDate will be added
     * at the beginning of the list.
     *
     * @param \AlphaRPC\Common\Scheduler\Schedule $schedule1
     * @param \AlphaRPC\Common\Scheduler\Schedule $schedule2
     *
     * @return int
     */
    protected function compare($schedule1, $schedule2)
    {
        $run1 = $schedule1->getNextRunDate();
        $run2 = $schedule2->getNextRunDate();
        if ($run1 < $run2) {
            return 1;
        } elseif ($run1 > $run2) {
            return -1;
        }

        return 0;
    }
}
