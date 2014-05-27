<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage StorageHandler
 */

namespace AlphaRPC\Manager\Storage;

use ArrayAccess;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage StorageHandler
 */
abstract class AbstractStorage implements ArrayAccess, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Set a key-value pair.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     * @throws \RuntimeException
     */
    abstract public function set($key, $value);

    /**
     * Retrieve a key-value pair.
     *
     * @param string $key
     *
     * @return mixed|null
     * @throws \RuntimeException
     */
    abstract public function get($key);

    /**
     * Check for the existence of a key.
     *
     * @param string $key
     *
     * @return boolean
     */
    abstract public function has($key);

    /**
     * Remove a key-value pair.
     *
     * @param string $key
     *
     * @return mixed
     * @throws \RuntimeException
     */
    abstract public function remove($key);

    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns a Logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
