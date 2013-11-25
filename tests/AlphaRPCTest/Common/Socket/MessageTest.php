<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Common\Socket;

use AlphaRPC\Common\Socket\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testPeekReturnsTheFirstPartButDoesNotRemoveIt()
    {
        $msg = new Message(array('hello', 'world'));
        $this->assertEquals('hello', $msg->peek());
        $this->assertSame(2, $msg->count());
    }

    public function testShiftReturnsTheFirstPartAndRemovesIt()
    {
        $msg = new Message(array('hello', 'world'));
        $this->assertEquals('hello', $msg->shift());
        $this->assertSame(1, $msg->count());
        $this->assertEquals('world', $msg->peek());
    }

    public function testToArrayRendersTheMessageAsAnArray()
    {
        $msg = new Message(array('hello', 'world'));
        $this->assertSame(array('hello', 'world'), $msg->toArray());
    }

    public function testHasRoutingInformationReturnsFalseWhenNoRoutingInformationIsPresent()
    {
        $msg = new Message(array('hello', 'world'));
        $this->assertFalse($msg->hasRoutingInformation());
    }

    public function testHasRoutingInformationReturnsTrueWhenRoutingInformationIsPresent()
    {
        $msg = new Message(array('worker1', '', 'hello', 'world'));
        $this->assertTrue($msg->hasRoutingInformation());

        $msg = new Message(array('worker1', 'dealer1', '', 'hello', 'world'));
        $this->assertTrue($msg->hasRoutingInformation());
    }

    public function testStripRoutingInformationDoesNothingWhenNoRoutingInformationIsPresent()
    {
        $msg = new Message(array('hello', 'world'));
        $msg->stripRoutingInformation();
        $this->assertSame(array(), $msg->getRoutingInformation());
        $this->assertSame(2, $msg->count());
    }

    public function testStripRoutingInformationRemovesItFromTheMainMessage()
    {
        $msg = new Message(array('worker1', '', 'hello', 'world'));
        $msg->stripRoutingInformation();

        $this->assertSame(array('hello', 'world'), $msg->toArray());
    }

    public function testStripRoutingInformationMakesRoutingInformationAvailableInGetroutinginformation()
    {
        $msg = new Message(array('worker1', '', 'hello', 'world'));
        $msg->stripRoutingInformation();

        $this->assertSame(array('worker1'), $msg->getRoutingInformation());
    }

    public function testPrependRoutingInformationPrependsTheRouting()
    {
        $msg = new Message(array('hello', 'world'));
        $msg->prependRoutingInformation(array('worker1'));
        $this->assertSame(array('worker1', ''), $msg->getRoutingInformation());
    }

    public function testPrependRoutingInformationRemovesEmptyPartsAtEndOfRouting()
    {
        $msg = new Message(array('hello', 'world'));
        $msg->prependRoutingInformation(array('worker1', '', ''));
        $this->assertSame(array('worker1', ''), $msg->getRoutingInformation());
    }

    public function testPrependRoutingInformationWithExistingRouting()
    {
        $msg = new Message(array('worker1', '', 'hello', 'world'));
        $msg->stripRoutingInformation();
        $msg->prependRoutingInformation(array('dealer1'));

        $this->assertSame(array('dealer1', '', 'worker1'), $msg->getRoutingInformation());
    }
}