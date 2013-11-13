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

namespace AlphaRPC\Common\Protocol;

use AlphaRPC\Common\Protocol\Exception\UnknownMessageException;
use AlphaRPC\Common\Protocol\Exception\UnknownProtocolVersionException;
use AlphaRPC\Common\Protocol\Message\MessageInterface;
use AlphaRPC\Common\Socket\Message;

/**
 * The MessageType Factory creates MessageTypes based on their type.
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @author Reen Lokum <reen@alphacomm.nl>
 *
 * @package AlphaRPC
 * @subpackage MessageTypes
 */
class MessageFactory
{
    /**
     * Contains a list of all known protocol versions.
     *
     * @var array
     */
    protected static $protocolVersions = array(
        1 => 1,
    );

    /**
     * Contains a list of all message types and their IDs.
     *
     * @var array
     */
    private static $types = array(
        // General errors (600 - 999)
        600  => 'AlphaRPC\Common\Protocol\Message\UnknownProtocolVersion',

        // ClientHandler / WorkerHandler (2000 - 2999)
        2001 => 'AlphaRPC\Manager\Protocol\ClientHandlerJobRequest',
        2002 => 'AlphaRPC\Manager\Protocol\ClientHandlerJobResponse',
        2003 => 'AlphaRPC\Manager\Protocol\WorkerHandlerStatus',

        // Manager / Worker (3000 - 3999)
        3001 => 'AlphaRPC\Worker\Protocol\Register',
        3002 => 'AlphaRPC\Worker\Protocol\GetJobRequest',
        3003 => 'AlphaRPC\Worker\Protocol\Heartbeat',
        3004 => 'AlphaRPC\Worker\Protocol\HeartbeatResponseWorkerhandler',
        3005 => 'AlphaRPC\Worker\Protocol\ActionNotFound',
        3006 => 'AlphaRPC\Worker\Protocol\JobResult',
        3007 => 'AlphaRPC\Worker\Protocol\Destroy',

        // Worker / Service (4000 - 4999)
        4001 => 'AlphaRPC\Worker\Protocol\ActionListRequest',
        4002 => 'AlphaRPC\Worker\Protocol\ActionListResponse',
        4003 => 'AlphaRPC\Worker\Protocol\ExecuteJobRequest',
        4004 => 'AlphaRPC\Worker\Protocol\ExecuteJobResponse',

        // Client / ClientHandler (5000 - 5999)
        5001 => 'AlphaRPC\Client\Protocol\ExecuteRequest',
        5002 => 'AlphaRPC\Client\Protocol\ExecuteResponse',
        5003 => 'AlphaRPC\Client\Protocol\FetchRequest',
        5004 => 'AlphaRPC\Client\Protocol\FetchResponse',
        5005 => 'AlphaRPC\Client\Protocol\NotFoundResponse',
        5006 => 'AlphaRPC\Client\Protocol\PoisonResponse',
        5007 => 'AlphaRPC\Client\Protocol\TimeoutResponse',

        // Management (9000 - 9999)
        9001 => 'AlphaRPC\Manager\Protocol\QueueStatusRequest',
        9002 => 'AlphaRPC\Manager\Protocol\QueueStatusResponse',
        9003 => 'AlphaRPC\Manager\Protocol\WorkerStatusRequest',
        9004 => 'AlphaRPC\Manager\Protocol\WorkerStatusResponse',
    );

    /**
     * Contains {@see ::$types} in reverse.
     *
     * @var array
     */
    protected static $typesByClass;

    /**
     * Create a Message with the given Type.
     *
     * @param Message $msg
     *
     * @return MessageInterface
     * @throws UnknownProtocolVersionException
     */
    public static function createProtocolMessage(Message $msg)
    {
        $version = $msg->shift();
        if (!self::hasProtocolVersion(($version))) {
            throw new UnknownProtocolVersionException();
        }

        $type = $msg->shift();
        $class = self::getClassByType($type);

        return $class::fromMessage($msg);
    }

    /**
     * Returns the Message class for the given Message type number.
     *
     * @param int $type
     *
     * @return string
     * @throws UnknownMessageException
     */
    private static function getClassByType($type)
    {
        if (!isset(self::$types[$type])) {
            throw new UnknownMessageException(
                'Unable to parse message because type ('.$type.')'
                .' is not mapped.'
            );
        }

        return self::$types[$type];
    }

    /**
     * Checks whether a message with a specific type exists.
     *
     * @param integer $type
     *
     * @return boolean
     */
    public static function hasMessageType($type)
    {
        return isset(self::$types[$type]);
    }

    /**
     * Get the Type ID for the given class.
     *
     * @param string $className
     *
     * @return integer
     * @throws UnknownMessageException
     */
    public static function getTypeIdByClass($className)
    {
        if (null === self::$typesByClass) {
            self::$typesByClass = array_flip(self::$types);
        }

        if (!isset(self::$typesByClass[$className])) {
            throw new UnknownMessageException('Class not registered: '.$className.'.');
        }

        return self::$typesByClass[$className];
    }

    /**
     * Returns the latest Protocol Version.
     *
     * @return integer
     */
    public static function getLatestProtocolVersion()
    {
        return max(self::$protocolVersions);
    }

    /**
     * Check whether we know the given protocol version.
     *
     * @param integer $version
     *
     * @return boolean
     */
    public static function hasProtocolVersion($version)
    {
        return isset(self::$protocolVersions[$version]);
    }

    /**
     * @param Message $message
     *
     * @return boolean
     */
    public static function isProtocolMessage(Message $message)
    {
        // Temporary check to maintain BC with legacy Messages.
        if (is_numeric($message->peek()) && $message->peek() < 100) {
            return true;
        }

        return false;
    }
}
