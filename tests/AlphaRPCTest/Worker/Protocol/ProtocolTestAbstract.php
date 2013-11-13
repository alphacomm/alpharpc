<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Worker\Protocol;

use AlphaRPC\Worker\Protocol\Register as ProtocolMessage;

class ProtocolTestAbstract extends \PHPUnit_Framework_TestCase
{
    public function testTheMessageIsSerializableToItself()
    {
        $pm = new ProtocolMessage();
        $pm->setActions(array('service1', 'service2'));
        $pm2 = ProtocolMessage::fromMessage($pm->toMessage());

        $this->assertEquals($pm, $pm2);
    }
}