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

namespace AlphaRPC\Common;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
class PidTracker
{
    /**
     * Contains the PID of the child process.
     *
     * @var int
     */
    protected $pid;

    /**
     * Fork the process and run the callable in the child process.
     *
     * @param callable $callable
     *
     * @return \PidTracker
     * @throws \RuntimeException
     */
    public static function fork($callable)
    {
        if (!is_callable($callable)) {
            throw new \RuntimeException('Callable is not callable.');
        }
        $pid = pcntl_fork();
        if ($pid < 0) {
            throw new \RuntimeException('Unable to fork');
        }

        if ($pid == 0) {
            call_user_func($callable);
            exit;
        }

        $tracker = new PidTracker($pid);

        return $tracker;
    }

    /**
     *
     * @param int $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    /**
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Checks whether the process is still alive.
     *
     * @return boolean
     */
    public function isAlive()
    {
        if ($this->pid < 1) {
            return false;
        }

        $status = null;
        $pid = pcntl_wait($status, WNOHANG);

        switch ($pid) {
            case -1:
                throw new \RuntimeException('pcntl_wait failed.');

            case $this->pid:
                $this->pid = -1;

                return false;

            case 0:
                return true;
        }

        // Other pid exited.
        return true;
    }

    /**
     * Kill the process.
     *
     * This sends a SIGTERM to the process.
     *
     * @return boolean
     */
    public function kill()
    {
        if ($this->pid < 1) {
            return;
        }

        if (!posix_kill($this->pid, SIGTERM)) {
            throw new \RuntimeException('Unable to kill process, PID: '.$this->pid.'.');
        }

        return;
    }

    /**
     * Kill the process and wait for it to finish.
     */
    public function killAndWait()
    {
        $status = 0;
        $this->kill();
        if ($this->pid > 0) {
            $pid = pcntl_wait($status);
            if ($pid == $this->pid) {
                $this->pid = -1;
            }
        }
    }
}
