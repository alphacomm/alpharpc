<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Common\Protocol\Message;

use AlphaRPC\Common\Protocol\Message\UnknownProtocolVersion as ProtocolMessage;

class UnkownProtocolVersionTest extends \PHPUnit_Framework_TestCase
{
    public function testRoundtrip()
    {
        $upv = new ProtocolMessage();
        $upv2 = ProtocolMessage::fromMessage($upv->toMessage());

        $this->assertEquals($upv, $upv2);
    }
}
