<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Common\MessageStream;

use AlphaRPC\Common\MessageStream\ArrayStream;
use AlphaRPC\Manager\Protocol\QueueStatusRequest;

class MessageStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayStream
     */
    protected $stream;

    public function setUp()
    {
        $this->stream = new ArrayStream();
    }

    public function testReadingAMessageWorks()
    {
        $request = new QueueStatusRequest();

        $this->stream->addMessageToRead($request);
        $read = $this->stream->read();

        $this->assertEquals($request, $read);
    }
}
