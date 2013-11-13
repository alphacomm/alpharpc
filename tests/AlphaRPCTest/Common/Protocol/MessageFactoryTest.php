<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Common\Protocol;

use AlphaRPC\Common\Protocol\Exception\UnknownProtocolVersionException;
use AlphaRPC\Common\Protocol\MessageFactory;
use AlphaRPC\Common\Socket\Message;
use AlphaRPC\Common\AlphaRPC;

class MessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testIsProtocolMessage()
    {
        $msg1 = new Message(array('1'));
        $this->assertTrue(MessageFactory::isProtocolMessage($msg1));

        $msg3 = new Message(array(99));
        $this->assertTrue(MessageFactory::isProtocolMessage($msg3));

        $msg2 = new Message(array(100));
        $this->assertFalse(MessageFactory::isProtocolMessage($msg2));
    }

    public function testHasProtocolVersion()
    {
        $msg1 = new Message(array('1'));
        $this->assertTrue(MessageFactory::hasProtocolVersion($msg1->peek()));

        $msg2 = new Message(array(-1));
        $this->assertFalse(MessageFactory::hasProtocolVersion($msg2->peek()));
    }

    public function testGetLastestProtocolVersion()
    {
        $this->assertSame(1, MessageFactory::getLatestProtocolVersion());
    }

    public function testHasMessageType()
    {
        $this->assertTrue(MessageFactory::hasMessageType(4001));
        $this->assertFalse(MessageFactory::hasMessageType(-1));
    }

    public function testCreateProtocolMessageWithValidProtocolVersionAndMessageTypeReturnsAMessage()
    {
        $msg = new Message(array(1, 600));
        $expected = 'AlphaRPC\Common\Protocol\Message\UnknownProtocolVersion';
        $this->assertInstanceOf($expected, MessageFactory::createProtocolMessage($msg));
    }

    /**
     * @expectedException \AlphaRPC\Common\Protocol\Exception\UnknownProtocolVersionException
     */
    public function testCreateProtocolMessageWithoutValidProtocolVersionThrowsException()
    {
        $msg = new Message(array(99));
        MessageFactory::createProtocolMessage($msg);
    }

    /**
     * @expectedException \AlphaRPC\Common\Protocol\Exception\UnknownMessageException
     */
    public function testCreateProtocolMessageWithoutValidMessagetypeThrowsException()
    {
        $msg = new Message(array(1, 999));
        MessageFactory::createProtocolMessage($msg);
    }

    public function testGetTypeIdByClassReturnsTypeIdWhenGivenACorrectClass()
    {
        $class = 'AlphaRPC\Common\Protocol\Message\UnknownProtocolVersion';
        $expected = 600;

        $this->assertSame($expected, MessageFactory::getTypeIdByClass($class));
    }

    /**
     * @expectedException \AlphaRPC\Common\Protocol\Exception\UnknownMessageException
     * @expectedExceptionMessage Class not registered: AlphaRPC\Common\Protocol\Message\NonExistentMessage.
     */
    public function testGetTypeIdByClassThrowsExceptionWithIncorrectClass()
    {
        $class = 'AlphaRPC\Common\Protocol\Message\NonExistentMessage';
        MessageFactory::getTypeIdByClass($class);
    }
}