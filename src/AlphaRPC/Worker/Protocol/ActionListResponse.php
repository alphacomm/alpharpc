<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Worker\Protocol;

use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;

class ActionListResponse extends MessageAbstract
{
    /**
     * List of actions registered to the service.
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Create a new StorageError message.
     */
    public function __construct($actions)
    {
        $this->setActions($actions);
    }

    /**
     *
     * @param array $actions
     *
     * @return \AlphaRPC\Worker\Protocol\ActionListResponse
     * @throws \RuntimeException
     */
    public function setActions($actions)
    {
        $this->actions = array();
        foreach ($actions as $action) {
            if (!is_string($action)) {
                throw new \RuntimeException('Action should be a string.');
            }

            $this->actions[] = $action;
        }

        return $this;
    }

    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Creates a ActionResponse message from the Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @return StorageStatus
     */
    public static function fromMessage(Message $msg)
    {
        return new self($msg->toArray());
    }

    /**
     * Create a new Message from this ActionResponse.
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function toMessage()
    {
        return parent::createMessage($this->getActions());
    }
}
