<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Common\Socket;

use ZMQ;
use ZMQContext;
use ZMQSocket;

class Factory
{
    const MODE_CONNECT = 'connect';
    const MODE_BIND = 'bind';
    const MODE_CONSTRUCT = 'construct';

    /**
     *
     * @var ZMQContext
     */
    protected $context = null;

    /**
     *
     * @var array
     */
    protected $options = array();

    /**
     * The keys of this array contain the valid SOCKOPT_ value. The value
     * contains the constant name.
     *
     * @var array|null
     */
    protected static $validOptions = null;

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Clears the current options.
     *
     * @return \AlphaRPC\Common\Socket\Factory
     */
    public function clearOptions()
    {
        $this->options = array();

        return $this;
    }

    /**
     * Fills the attribute validOptions containing a list of options that
     * can be set.
     *
     * @return null
     */
    protected static function parseValidKeys()
    {
        if (self::$validOptions !== null) {
            return;
        }

        $refl = new ReflectionClass('\ZMQ');
        $const = $refl->getConstants();
        self::$validOptions = array();
        foreach ($const as $key => $value) {
            if (substr($key, 0, 8) == 'SOCKOPT_') {
                self::$validOptions[$value] = '\ZMQ::'.$key;
            }
        }
    }

    /**
     * Checks if the given option key is valid for ZMQ.
     *
     * @param mixed $key
     *
     * @return boolean
     */
    public function isOptionKeyValid($key)
    {
        self::parseValidKeys();

        if (isset(self::$validOptions[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Overrides a default ZMQ option.
     *
     * @param int   $key
     * @param mixed $value
     *
     * @return \AlphaRPC\Common\Socket\Factory
     * @throws \RuntimeException
     */
    public function addOption($key, $value)
    {
        if (!$this->isOptionKeyValid($key)) {
            throw new \RuntimeException('Invalid socket option '.$key.'.');
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Adds ZMQ socket options.
     *
     * @param array $options
     *
     * @return \AlphaRPC\Common\Socket\Factory
     * @throws \RuntimeException
     */
    public function addOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->addOption($key, $value);
        }

        return $this;
    }

    /**
     *
     * @param array $options
     *
     * @return \AlphaRPC\Common\Socket\Factory
     */
    public function setOptions($options)
    {
        $this->clearOptions();
        $this->addOptions($options);

        return $this;
    }

    /**
     * Creates a publiser socket.
     *
     * @param string       $mode
     * @param string|array $dsn
     * @param array        $options
     *
     * @return Socket
     */
    public function createPublisher($mode, $dsn = null, $options = array())
    {
        return $this->createSocket(ZMQ::SOCKET_PUB, $mode, $dsn, $options);
    }

    /**
     * Creates a subscriber socket.
     *
     * @param string       $mode
     * @param string       $subscribe
     * @param string|array $dsn
     * @param array        $options
     *
     * @return Socket
     */
    public function createSubscriber($mode, $dsn = null, $subscribe = '', $options = array())
    {
        $options[ZMQ::SOCKOPT_SUBSCRIBE] = $subscribe;

        return $this->createSocket(ZMQ::SOCKET_SUB, $mode, $dsn, $options);
    }

    /**
     * Creates a request socket.
     *
     * @param string       $mode
     * @param string|array $dsn
     * @param array        $options
     *
     * @return Socket
     */
    public function createRequest($mode, $dsn = null, $options = array())
    {
        return $this->createSocket(ZMQ::SOCKET_REQ, $mode, $dsn, $options);
    }

    /**
     * Creates a reply socket.
     *
     * @param string       $mode
     * @param string|array $dsn
     * @param array        $options
     *
     * @return Socket
     */
    public function createReply($mode, $dsn = null, $options = array())
    {
        return $this->createSocket(ZMQ::SOCKET_REP, $mode, $dsn, $options);
    }

    /**
     * Creates a router socket.
     *
     * @param string       $mode
     * @param string|array $dsn
     * @param array        $options
     *
     * @return Socket
     */
    public function createRouter($mode, $dsn = null, $options = array())
    {
        return $this->createSocket(ZMQ::SOCKET_ROUTER, $mode, $dsn, $options);
    }

    /**
     * Creates a socket.
     *
     * @param int    $type
     * @param string $mode
     * @param string $dsn
     * @param array  $options
     *
     * @return Socket
     */
    protected function createSocket($type, $mode, $dsn = null, $options = array())
    {
        $context = $this->getContext();
        $socket = new Socket($context, $type);

        $options = $this->options + $options;
        foreach ($options as $key => $value) {
            $socket->setSockOpt($key, $value);
        }

        $this->connect($socket, $mode, $dsn);

        return $socket;
    }

    /**
     * Connects or binds a socket based on mode.
     *
     * @param ZMQSocket    $socket
     * @param string       $mode
     * @param string|array $dsn
     *
     * @return \AlphaRPC\Common\Socket\Factory
     * @throws \RuntimeException
     */
    public function connect(ZMQSocket $socket, $mode, $dsn)
    {
        if ($mode == self::MODE_CONSTRUCT) {
            return $this;
        }

        $func = null;
        if ($mode == self::MODE_BIND) {
            $func = 'bind';
        } elseif ($mode == self::MODE_CONNECT) {
            $func = 'connect';
        }

        if (is_string($dsn)) {
            $dsn = array($dsn);
        }

        if (!is_array($dsn)) {
            throw new \RuntimeException('DSN should be a string or an array.');
        }

        foreach ($dsn as $d) {
            if (!is_string($d)) {
                throw new \RuntimeException('Non-string in DSN array detected.');
            }
            $socket->$func($d);
        }
    }

    /**
     * Set the context to use for future sockets.
     * @param ZMQContext $context
     *
     * @return \AlphaRPC\Common\Socket\Factory
     */
    public function setContext(ZMQContext $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     *
     * @return ZMQContext
     */
    public function getContext()
    {
        if ($this->context === null) {
            $this->context = new ZMQContext();
        }

        return $this->context;
    }
}
