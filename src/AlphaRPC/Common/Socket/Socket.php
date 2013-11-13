<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */

namespace AlphaRPC\Common\Socket;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use ZMQ;
use ZMQPoll;
use ZMQSocket;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
class Socket extends ZMQSocket implements LoggerAwareInterface
{
    /**
     * Contains the Logger.
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    protected static $nextId = 0;

    protected $id = null;

    protected $verbose = false;

    /**
     * Create a new Socket.
     *
     * @param \ZMQContext     $context      The Context to create this Socket with
     * @param integer         $type         One of the ZMQ::SOCKET_{PUB,SUB,PUSH,PULL,REQ,REP,ROUTER,DEALER} contants.
     * @param LoggerInterface $logger       A Logger
     * @param string          $persistentId When using a persistent socket: the persistence ID
     * @param callable|null   $onNewSocket  Callback to use when a new socket is created
     *
     * @return \AlphaRPC\Common\Socket\Socket
     */
    public static function create($context, $type, LoggerInterface $logger = null, $persistentId = null, $onNewSocket = null)
    {
        if (!is_callable($onNewSocket)) {
            $onNewSocket = null;
        }

        $newSocket = false;
        $callback = function() use ($onNewSocket, &$newSocket) {
            $newSocket = true;
            if ($onNewSocket !== null) {
                $onNewSocket();
            }
        };

        $instance = new self($context, $type, $persistentId, $callback);
        $instance->setId(self::$nextId);
        $instance->setNewSocket($newSocket);
        $instance->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        self::$nextId++;

        if (null !== $logger) {
            $instance->setLogger($logger);
        }

        return $instance;
    }

    /**
     * Poll for new Events on this Socket.
     *
     * @param integer $timeout Timeout in milliseconds. Defaults to 0 (return immediately).
     *
     * @return boolean
     */
    public function hasEvents($timeout = 0)
    {
        $poll = new ZMQPoll();
        $poll->add($this, ZMQ::POLL_IN);
        $read = $write = array();
        $events = $poll->poll($read, $write, $timeout);
        if ($events > 0) {
            return true;
        }

        return false;
    }

    /**
     * Set the ID of this socket.
     *
     * @param mixed $id
     *
     * @return Socket
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Enable or disable verbose logging.
     *
     * @param boolean $verbose
     *
     * @return Socket
     */
    public function setVerbose($verbose)
    {
        $this->verbose = (bool) $verbose;

        return $this;
    }

    /**
     * Internal helper function to log whether a new or an old socket is used.
     *
     * @param boolean $newSocket
     *
     * @internal
     *
     * @return Socket
     */
    public function setNewSocket($newSocket)
    {
        if (!$newSocket) {
            $this->getLogger()->debug('An old ZMQSocket is being reused.');
        }

        return $this;
    }

    /**
     * Return the ID of this socket.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Receive a Message.
     *
     * Use this instead of {@see ::recv}.
     *
     * @param int $mode
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function mrecv($mode = 0)
    {
        if ($this->verbose) {
            $this->getLogger()->debug('PRE RECV: '.$this->id);
        }
        $data = array();
        while (true) {
            $data[] = $this->recv($mode);
            if (!$this->getSockOpt(ZMQ::SOCKOPT_RCVMORE)) {
                break;
            }
        }

        $message = new Message($data);
        if ($this->verbose) {
            $this->getLogger()->debug('RECV: '.$this->id);
            $this->getLogger()->debug($message);
        }

        if (ZMQ::SOCKET_ROUTER === $this->getSocketType()) {
            $message->stripRoutingInformation();
        }

        return $message;
    }

    /**
     * Send a Messsage.
     *
     * @param Message $msg
     *
     * @return Socket
     */
    public function msend(Message $msg)
    {
        if (ZMQ::SOCKET_ROUTER === $this->getSocketType()) {
            $msg->prepend($msg->getRoutingInformation());
        }

        if ($this->verbose) {
            $this->getLogger()->debug('SEND: '.$this->id);
            $this->getLogger()->debug($msg);
        }
        $parts = $msg->toArray();
        $iMax = count($parts)-1;
        if ($iMax < 0) {
            throw new RuntimeException('No parts to send.');
        }
        for ($i = 0; $i < $iMax; $i++) {
            $this->send($parts[$i], ZMQ::MODE_SNDMORE);
        }
        $this->send($parts[$iMax]);
        if ($this->verbose) {
            $this->getLogger()->debug('Message sent.');
        }

        return $this;
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger
     *
     * @return AlphaRPC
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns the Logger.
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

    /**
     * Destruct this socket.
     */
    public function __destruct()
    {
        $this->getLogger()->debug('Destruct socket '.$this->id.'!');
    }

    public function getStream()
    {
        return new Stream($this);
    }
}
