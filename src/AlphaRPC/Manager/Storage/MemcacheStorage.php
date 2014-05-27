<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage StorageHandler
 */

namespace AlphaRPC\Manager\Storage;

use Memcached;
use Psr\Log\LogLevel;

/**
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage StorageHandler
 */
class MemcacheStorage extends AbstractStorage
{
    /**
     * The configuration.
     *
     * @var array
     */
    protected $config = array();

    /**
     *
     * @var Memcached
     */
    protected $memcached = null;

    /**
     * The Memcached server host.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * The Memcached server port.
     *
     * @var string
     */
    protected $port = '11211';

    /**
     * A prefix to add before all keys.
     *
     * The prefix makes it possible to use multiple
     * environments that use the same keys, without
     * running the risk of conflicts.
     *
     * @var string
     */
    protected $prefix = null;

    /**
     * Magic string to indicate this value is split.
     */
    const VALUE_SPLIT = '_____SPLIT_VALUE_____';

    /**
     * Maximum size of a value that fits into Memcached (1MiB).
     */
    const VALUE_MAX_SIZE = 1048576;

    /**
     * Value size for split values.
     *
     * Is set to approximately 0.999 MiB.
     */
    const VALUE_SPLIT_SIZE = 1047527;

    /**
     * Create a new Memcache Store.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
        $this->connect();
    }

    /**
     * Connect to Memcache.
     */
    private function connect()
    {
        $memcache = new Memcached();
        $memcache->addServer($this->host, $this->port);

        if (null !== $this->prefix) {
            $memcache->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
        }

        $this->memcached = $memcache;
    }

    public function get($key)
    {
        $value = $this->memcached->get($key);

        if ($value === false) {
            $resultCode = $this->memcached->getResultCode();
            if ($resultCode === Memcached::RES_NOTFOUND) {
                return null;
            }

            if ($resultCode !== Memcached::RES_SUCCESS) {
                $msg = sprintf(
                    'Unable to retrieve key "%s": %s.',
                    $key,
                    $this->memcached->getResultMessage()
                );

                $this->getLogger()->log(LogLevel::NOTICE, $msg);
                throw new \RuntimeException($msg);
            }
        }

        if ($this->isSplit($value)) {
            return $this->getSplit($key, $this->getNumberOfParts($key, $value));
        }

        return $value;
    }

    public function has($key)
    {
        try {
            if ($this->get($key) === null) {
                return false;
            }
        } catch (\RuntimeException $e) {
            return false;
        }

        return true;
    }

    public function remove($key)
    {
        if (!$this->has($key)) {
            return;
        }

        $value = $this->memcached->get($key);
        if ($this->isSplit($value)) {
            $this->removeSplit($key, $this->getNumberOfParts($key, $value));
            return;
        }

        $success = $this->memcached->delete($key);

        if (!$success) {
            $msg = sprintf(
                'Unable to remove value for key "%s": %s.',
                $key,
                $this->memcached->getResultMessage()
            );

            $this->getLogger()->log(LogLevel::NOTICE, $msg);
            throw new \RuntimeException($msg);
        }
    }

    public function set($key, $value)
    {
        $success = $this->memcached->set($key, $value);
        if (!$success) {
            // Try reconnect
            $this->connect();

            // If the value is too big, split it
            // and set the individual parts.
            if (strlen($value) > self::VALUE_MAX_SIZE) {
                return $this->setSplit($key, $value);
            }

            $success = $this->memcached->set($key, $value);

            if (!$success) {
                $msg = sprintf(
                    'Unable to store value for key "%s": %s.',
                    $key,
                    $this->memcached->getResultMessage()
                );

                $this->getLogger()->log(LogLevel::NOTICE, $msg);
                throw new \RuntimeException($msg);
            }
        }

        return $value;
    }

    /**
     * Split a value in different parts and add them one by one.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     * @throws \RuntimeException
     */
    private function setSplit($key, $value)
    {
        $values = str_split($value, self::VALUE_SPLIT_SIZE);

        $to_set       = array();
        $to_set[$key] = self::VALUE_SPLIT.count($values);

        foreach ($values as $part_nr => $v) {
            $to_set[$key.$part_nr] = $v;
        }

        if (false === $this->memcached->setMulti($to_set)) {
            $msg = sprintf(
                'Unable to store split values for key "%s": %s.',
                $key,
                $this->memcached->getResultMessage()
            );

            $this->getLogger()->log(LogLevel::NOTICE, $msg);
            throw new \RuntimeException($msg);
        }

        return $value;
    }

    /**
     * Retrieve the individual parts of a split value.
     *
     * @param string  $key
     * @param integer $parts
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getSplit($key, $parts)
    {
        $keys = array();
        for ($i = 0; $i < $parts; $i++) {
            $keys[] = $key.$i;
        }

        $values = $this->memcached->getMulti($keys);

        if (false === $values) {
            $msg = sprintf(
                'Unable to retrieve split key "%s": %s.',
                $key,
                $this->memcached->getResultMessage()
            );

            $this->getLogger()->log(LogLevel::NOTICE, $msg);
            throw new \RuntimeException($msg);
        }

        $value = implode('', $values);

        return $value;
    }

    /**
     * Remove the individual items of a split value.
     *
     * @param string  $key
     * @param integer $parts
     *
     * @throws \RuntimeException
     */
    private function removeSplit($key, $parts)
    {
        $keys   = array();
        $keys[] = $key;
        for ($i = 0; $i < $parts; $i++) {
            $keys[] = $key.$i;
        }

        if (false === $this->memcached->deleteMulti($keys)) {
            $msg = sprintf(
                'Unable to remove split values for key "%s": %s.',
                $key,
                $this->memcached->getResultMessage()
            );

            $this->getLogger()->log(LogLevel::NOTICE, $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Checks whether a value is split into multiple parts.
     *
     * @param string $value
     *
     * @return boolean
     */
    private function isSplit($value)
    {
        return self::VALUE_SPLIT == substr($value, 0, strlen(self::VALUE_SPLIT));
    }

    /**
     * Returns the number if parts for the split value.
     *
     * @param string $key
     * @param string $value
     *
     * @return integer
     * @throws \RuntimeException
     */
    private function getNumberOfParts($key, $value)
    {
        $parts = substr($value, strlen(self::VALUE_SPLIT));

        if (!ctype_digit($parts) || $parts > PHP_INT_MAX) {
            throw new \RuntimeException(
                'Number of parts in split value for key "'.$key.'" is invalid.'
            );
        }

        // We know it's an integer. Now also force
        // its data type to be an integer.
        return (int) $parts;
    }

    /**
     * Set the configuration.
     *
     * @param array $config
     *
     * @return \AlphaRPC\Manager\Storage\MemcacheStorage
     */
    protected function setConfig(array $config = array())
    {
        $this->config = $config;

        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }

        if (isset($config['host'])) {
            $this->host = $config['host'];
        }

        if (isset($config['port'])) {
            $this->port = $config['port'];
        }

        return $this;
    }
}
