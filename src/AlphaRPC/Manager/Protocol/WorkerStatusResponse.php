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
class WorkerStatusResponse extends MessageAbstract
{
    /**
     * Contains the worker status.
     *
     * @var array
     */
    protected $workerStatus;

    /**
     *
     * @param array $workerStatus
     */
    public function __construct(array $workerStatus)
    {
        $this->workerStatus = $workerStatus;
    }

    /**
     * Returns status of the workers.
     *
     * @return array
     */
    public function getWorkerStatus()
    {
        return $this->workerStatus;
    }

    /**
     * Creates an instance from the Message.
     *
     * @param Message $msg
     *
     * @return WorkerStatusResponse
     */
    public static function fromMessage(Message $msg)
    {
        $workerStatus = array();
        while ($msg->peek() !== null) {
            $id = $msg->shift();
            $actionCount = (int) $msg->shift();
            $actionList = array();
            for ($i = 0; $i < $actionCount; $i++) {
                $actionList[] = $msg->shift();
            }

            $workerStatus[$id] = array(
                'id'          => $id,
                'actionList'  => $actionList,
                'current'     => $msg->shift(),
                'ready'       => (bool) $msg->shift(),
                'valid'       => (bool) $msg->shift(),
            );
        }

        return new self($workerStatus);
    }

    /**
     * Create a new Message from this instance.
     *
     * @return Message
     */
    public function toMessage()
    {
        $m = new Message();

        /* @var $w Worker */
        foreach ($this->workerStatus as $w) {
            $m->push($w['id']);
            $m->push(count($w['actionList']));
            $m->append($w['actionList']);
            $m->push($w['current']);
            $m->push($w['ready']);
            $m->push($w['valid']);
        }

        return $m;
    }
}
