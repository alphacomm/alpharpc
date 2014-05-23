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
        $this->memcached = $memcache;
    }

    /**
     * Puts the prefix before the key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getKey($key)
    {
        return $this->prefix.$key;
    }

    public function get($key)
    {
        $key   = $this->getKey($key);
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

        return $value;
    }

    public function has($key)
    {
        try {
            $key = $this->getKey($key);
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

        $key = $this->getKey($key);
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
        $key     = $this->getKey($key);
        $success = $this->memcached->set($key, $value);
        if (!$success) {
            // Try reconnect
            $this->connect();
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
