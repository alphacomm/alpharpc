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

        if (false !== strpos($value, self::VALUE_SPLIT)) {
            // Value was too big, and was therefore split.
            // Retrieve the individual parts.
            return $this->getSplit($key, substr($value, strlen(self::VALUE_SPLIT)));
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
        if (false !== strpos($value, self::VALUE_SPLIT)) {
            // Value was too big, and was therefore split.
            // Remove the individual parts.
            $this->removeSplit($key, substr($value, strlen(self::VALUE_SPLIT)));
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
            $success = $this->memcached->set($key, $value);

            if (!$success) {
                // If the key is too big, set it with different parts.
                // Result code 37 = ITEM TOO BIG
                if (37 == $this->memcached->getResultCode()) {
                    // Item too big. Split in parts.
                    return $this->setSplit($key, $value);
                }

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
        // Split in values of 0.99MiB.
        $values = str_split($value, 0.99 * 1024 * 1024);

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
