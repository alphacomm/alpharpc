<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Client
 */

namespace AlphaRPC\Client;

use AlphaRPC\Common\Socket\Socket;
use AlphaRPC\Common\TimeoutException;
use AlphaRPC\Common\Timer\TimeoutTimer;
use AlphaRPC\Manager\Protocol\QueueStatusRequest;
use AlphaRPC\Manager\Protocol\QueueStatusResponse;
use AlphaRPC\Manager\Protocol\WorkerStatusRequest;
use AlphaRPC\Manager\Protocol\WorkerStatusResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use ZMQ;
use ZMQContext;

/**
 * AlphaRPCStatus used to be a client in ZM1, and it will be in the future, however
 * for now, to get the work done, we are connecting directly to the
 * workerhandler.
 */

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Client
 */
class AlphaRPCStatus
{
    /**
     *
     * @var Socket
     */
    protected $socket = null;

    /**
     *
     * @var callable|null
     */
    protected $logger = null;

    /**
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Requests the queue status.
     *
     * @param string $manager
     *
     * @return array
     * @throws \RuntimeException
     */
    public function queueStatus($manager)
    {
        $socket = $this->getSocket($manager);
        $stream = $socket->getStream();

        $stream->send(new QueueStatusRequest());

        $msg = $stream->read(new TimeoutTimer(1500));
        if (!$msg instanceof QueueStatusResponse) {
            throw new RuntimeException('Invalid response: '.get_class($msg).'.');
        }

        return $msg->getQueueStatus();
    }

    /**
     *
     * @param string $manager
     *
     * @return array
     * @throws RuntimeException
     * @throws TimeoutException
     */
    public function workerStatus($manager)
    {
        $socket = $this->getSocket($manager);
        $stream = $socket->getStream();

        $stream->send(new WorkerStatusRequest());

        $msg = $stream->read(new TimeoutTimer(1500));
        if (!$msg instanceof WorkerStatusResponse) {
            throw new RuntimeException('Invalid response: '.get_class($msg).'.');
        }

        return $msg->getWorkerStatus();
    }

    /**
     *
     * @param string $manager
     *
     * @return Socket
     */
    protected function getSocket($manager)
    {
        $context = new ZMQContext();
        $socket = Socket::create($context, ZMQ::SOCKET_REQ, $this->logger);
        $socket->connect($manager);

        return $socket;
    }
}
