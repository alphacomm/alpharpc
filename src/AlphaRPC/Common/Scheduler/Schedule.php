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

use AlphaRPC\Exception\RuntimeException;
use AlphaRPC\Exception\InvalidArgumentException;
use Cron\CronExpression;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
class Schedule
{
    /**
     * Name of the schedule useful for logging.
     *
     * @var string
     */
    protected $name;

    /**
     * Parsed cron expression.
     *
     * @var \Cron\CronExpression
     */
    protected $cronExpression;

    /**
     * Task to execute.
     *
     * @var callable
     */
    protected $task;

    /**
     * Timestamp when to run next, null if it should still be calculated.
     *
     * @var int|null
     */
    protected $nextRunDate = null;

    /**
     *
     * @param string                      $name
     * @param string|\Cron\CronExpression $expression
     * @param callable                    $task
     *
     * @throws RuntimeException
     */
    public function __construct($name, $expression, $task)
    {
        if (!class_exists('Cron\CronExpression')) {
            throw new RuntimeException('Missing dependency. Add "mtdowling/cron-expression" to composer.json.');
        }
        $this->setName($name);
        $this->setExpression($expression);
        $this->setTask($task);
    }

    /**
     * Set the name of the schedule.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    protected function setName($name)
    {
        if (!is_scalar($name)) {
            throw new InvalidArgumentException('Schedule name must be a string.');
        }
        $this->name = (string) $name;
    }

    /**
     * Get the name of the schedule.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the cron expression.
     *
     *
     * *    *    *    *    *    *
     * |    |    |    |    |    |
     * |    |    |    |    |    + year [optional]
     * |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
     * |    |    |    +---------- month (1 - 12)
     * |    |    +--------------- day of month (1 - 31)
     * |    +-------------------- hour (0 - 23)
     * +------------------------- min (0 - 59)
     *
     * @param string|\Cron\CronExpression $expression
     *
     * @throws InvalidArgumentException
     */
    protected function setExpression($expression)
    {
        if (is_string($expression)) {
            $expression = CronExpression::factory($expression);
        } elseif (!$expression instanceof CronExpression) {
            throw new InvalidArgumentException('Expression should be a string or an instanceof CronExpression.');
        }
        $this->cronExpression = $expression;
    }

    /**
     * Get the cron expression.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->cronExpression->getExpression();
    }

    /**
     * The task to run.
     *
     * @param callable $task
     *
     * @throws InvalidArgumentException
     */
    protected function setTask($task)
    {
        if (!is_callable($task)) {
            throw new InvalidArgumentException('Schedule task must be callable.');
        }
        $this->task = $task;
    }

    /**
     * Returns the timestamp of the next run date.
     *
     * @return int
     */
    public function getNextRunDate()
    {
        if ($this->nextRunDate === null) {
            $nextRun = $this->cronExpression->getNextRunDate();
            $this->nextRunDate = $nextRun->getTimestamp();
        }

        return $this->nextRunDate;
    }

    /**
     * Should we run?
     *
     * @return boolean
     */
    public function isDue()
    {
        return $this->nextRunDate <= time();
    }

    /**
     * Executes the task.
     *
     * @return null
     */
    public function run()
    {
        $this->nextRunDate = null;
        call_user_func($this->task);
    }
}
