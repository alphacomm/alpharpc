<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Worker\Protocol;

use AlphaRPC\Common\AlphaRPC;
use AlphaRPC\Common\Protocol\Message\MessageAbstract;
use AlphaRPC\Common\Socket\Message;
use InvalidArgumentException;

class Register extends MessageAbstract
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
    public function __construct(array $actions = array())
    {
        $this->setActions($actions);
    }

    /**
     *
     * @param array $actions
     *
     * @return self
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

    /**
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Creates a FetchAction message from the Message.
     *
     * @param \AlphaRPC\Common\Socket\Message $msg
     *
     * @throws InvalidArgumentException
     *
     * @return self
     */
    public static function fromMessage(Message $msg)
    {
        $count = $msg->shift();

        if ($count != $msg->count()) {
            throw new InvalidArgumentException(
                'The action count is not the same as the actual number of actions.');
        }

        return new self($msg->toArray());
    }

    /**
     * Create a new Message from this FetchActions.
     *
     * @return \AlphaRPC\Common\Socket\Message
     */
    public function toMessage()
    {
        $msg = parent::createMessage();
        $msg->append(array(count($this->actions)));
        $msg->append($this->actions);

        return $msg;
    }
}
