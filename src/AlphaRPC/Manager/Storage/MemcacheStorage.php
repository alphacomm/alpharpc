<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage StorageHandler
 */

namespace AlphaRPC\Manager\Storage;

use Memcached;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
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

    public function __construct(array $config = array())
    {
        $this->setConfig($config);
        $this->connect();
    }

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
     * @return $key
     */
    protected function getKey($key)
    {
        return $this->prefix.$key;
    }

    public function get($key)
    {
        $key = $this->getKey($key);
        $value = $this->memcached->get($key);
        if ($value === false) {
            $resultCode = $this->memcached->getResultCode();
            if ($resultCode == Memcached::RES_NOTFOUND) {
                return null;
            }
        }

        return $value;
    }

    public function has($key)
    {
        $key = $this->getKey($key);
        if ($this->get($key) === null) {
            return false;
        }

        return true;
    }

    public function remove($key)
    {
        $key = $this->getKey($key);
        $value = $this->get($key);
        if ($value !== null) {
            // Memcache has a value stored for this key, so we need to delete it.
            $success = $this->memcached->delete($key);
            if (!$success) {
                throw new RuntimeException('Unable to remove value for key '.$key.'.');
            }
        }

        return $value;
    }

    public function set($key, $value)
    {
        $key = $this->getKey($key);
        $success = $this->memcached->set($key, $value);
        if (!$success) {
            // Try reconnect
            $this->connect();
            $success = $this->memcached->set($key, $value);
            if (!$success) {
                throw new \RuntimeException('Unable to store value for key '.$key.'.');
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
