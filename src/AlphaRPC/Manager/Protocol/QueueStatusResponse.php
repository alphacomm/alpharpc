<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage MessageTypes
 */

namespace AlphaRPC\Manager\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

/**
 * ClientHandlerJobRequest is the Message that the ClientHandler uses
 * to request the WorkerHandler to process a Job
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage MessageTypes
 */
class QueueStatusResponse extends MessageAbstract
{
    /**
     * Contains queue status.
     *
     * @var array
     */
    protected $queueStatus;

    /**
     *
     * @param array $queueStatus
     */
    public function __construct(array $queueStatus)
    {
        $this->queueStatus = $queueStatus;
    }

    /**
     * Get the status.
     *
     * @return array
     */
    public function getQueueStatus()
    {
        return $this->queueStatus;
    }

    /**
     * Creates an instance from the Message.
     *
     * @param Message $msg
     *
     * @return QueueStatusResponse
     */
    public static function fromMessage(Message $msg)
    {
        $queueStatus = array();
        while ($msg->peek() !== null) {
            $action = $msg->shift();
            $queueStatus[$action] = array(
                'action'    => $action,
                'queue'     => $msg->shift(),
                'busy'      => $msg->shift(),
                'available' => $msg->shift()
            );
        }

        return new self($queueStatus);
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        $m = new Message();
        foreach ($this->queueStatus as $action) {
            $m->push($action['action']);
            $m->push($action['queue']);
            $m->push($action['busy']);
            $m->push($action['available']);
        }

        return $m;
    }
}
