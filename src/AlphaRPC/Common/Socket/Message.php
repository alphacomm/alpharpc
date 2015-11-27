<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Common
 */

namespace AlphaRPC\Common\Socket;

use AlphaRPC\Exception\RuntimeException;

/**
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Common
 */
class Message implements \Countable
{
    /**
     * Contains an array of strings.
     *
     * @var array
     */
    protected $parts = array();

    /**
     * Contains the routing headers for this Message.
     *
     * This will be filled when the {@see stripRoutingInformation()}
     * method is called.
     *
     * @var array
     */
    protected $routing = array();

    /**
     * Create a new Message.
     *
     * The $parts parameter can be used
     * to set the initial Message parts.
     *
     * @param array $parts
     */
    public function __construct(array $parts = array())
    {
        $this->parts = $parts;
    }

    /**
     * Take a look at first part of the Message.
     *
     * @return string|null
     */
    public function peek()
    {
        if (!isset($this->parts[0])) {
            return null;
        }

        return $this->parts[0];
    }

    /**
     * Take a look at the last part of the Message.
     *
     * @return string|null
     */
    public function tail()
    {
        $last = count($this->parts) - 1;
        if ($last < 0) {
            return null;
        }

        return $this->parts[$last];
    }

    /**
     * Adds a part at the beginning of the message.
     *
     * @param string $part
     *
     * @return Message
     */
    public function unshift($part)
    {
        array_unshift($this->parts, $part);

        return $this;
    }

    /**
     * Shifts the first part from this message.
     *
     * @return string|null
     */
    public function shift()
    {
        return array_shift($this->parts);
    }

    /**
     * Pops the last part from this message.
     *
     * @return string|null
     */
    public function pop()
    {
        return array_pop($this->parts);
    }

    /**
     * Adds a part at the end of the message.
     *
     * @param string $part
     *
     * @return Message
     */
    public function push($part = null)
    {
        $this->parts[] = $part;

        return $this;
    }

    /**
     * Replaces the current message with the given array of parts.
     *
     * @param array $parts
     *
     * @return Message
     */
    public function setParts(array $parts)
    {
        $this->parts = $parts;

        return $this;
    }

    /**
     * Returns the parts of this message.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->parts;
    }

    /**
     * Returns the number of parts in this message.
     *
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * Append a the given parts to the Message.
     *
     * @param array|Message $parts
     *
     * @return Message
     * @throws RuntimeException
     */
    public function append($parts)
    {
        if ($parts instanceof Message) {
            $parts = $parts->toArray();
        } elseif (!is_array($parts)) {
            throw new RuntimeException('Trying to append non array ('.gettype($parts).').');
        }
        $this->parts = array_merge($this->parts, $parts);

        return $this;
    }

    /**
     * Prepend the given parts.
     *
     * @param array|Message $parts
     *
     * @return Message
     * @throws RuntimeException
     */
    public function prepend($parts)
    {
        if ($parts instanceof Message) {
            $parts = $parts->toArray();
        } elseif (!is_array($parts)) {
            throw new RuntimeException('Trying to prepend non array ('.gettype($parts).').');
        }

        $this->parts = array_merge($parts, $this->parts);

        return $this;
    }

    /**
     * Creates a hash from this message.
     *
     * @return string
     */
    public function hash()
    {
        return sha1(implode(',', $this->parts));
    }

    /**
     * Create renders the Message like it would be sent over the wire.
     *
     * This methot prepends the actual message with
     * ---- MESSAGE ---- and ends it with
     * -- END MESSAGE --
     *
     * @return string
     */
    public function __toString()
    {
        $string = '---- MESSAGE ----'.PHP_EOL;
        foreach ($this->parts as $part) {
            $len = strlen($part);
            if ($len == 17 && $part[0] === "\0") {
                $part = self::encodeUUID($part);
                $len = 'UUID';
            } else {
                $len = sprintf('%04d', $len);
            }
            $string .= '['.$len.'] '.$part.PHP_EOL;
        }
        $string .= '-- END MESSAGE --'.PHP_EOL;

        return $string;
    }

    /**
     * Encodes the binary UUID to it's hexadecimal representation.
     *
     * @param string $uuid
     *
     * @return string
     */
    public static function encodeUUID($uuid)
    {
        return '@'.bin2hex($uuid);
    }

    /**
     * Returns the Routing Information for this Message.
     *
     * @return array
     */
    public function getRoutingInformation()
    {
        return $this->routing;
    }

    /**
     * Check whether this Message has routing information.
     *
     * @return boolean
     */
    public function hasRoutingInformation()
    {
        foreach ($this->parts as $part) {
            // An empty part indicates the end of routing info.
            if (!$part) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepend the given routing information to the Message.
     *
     * @param array $routing
     */
    public function prependRoutingInformation(array $routing)
    {
        $count = count($routing);
        if (0 == $count) {
            return;
        }

        // Strip all the empty parts from the last to the first.
        $count--;
        while (!$routing[$count]) {
            unset($routing[$count]);
            $count--;
        }

        // Make sure the last part is always empty.
        $routing[] = '';

        // Prepend the routing and add a routing delimiter (empty part).
        $this->routing = array_merge($routing, $this->routing);
    }

    /**
     * Strips first routing headers from this message. This function assumes
     * there is a routing header present, results can be unexpected if not.
     *
     * @return Message
     */
    public function stripRoutingInformation()
    {
        if (!$this->hasRoutingInformation()) {
            return $this;
        }

        while (true) {
            // Remove the start of the array until an empty part is found.
            $part = array_shift($this->parts);
            if (!$part) {
                break;
            }
            $this->routing[] = $part;
        }

        return $this;
    }
}
