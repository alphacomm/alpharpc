<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPCTest\Manager\ClientHandler;

use AlphaRPC\Manager\ClientHandler\ClientBucket;

class ClientBucketTest extends \PHPUnit_Framework_TestCase
{
    protected $clientBucket;

    public function setUp()
    {
        // In the setUp method, you perform all actions that are necessary
        // to bootstrap the class you want to test.
        //
        // The setUp method is run before each test, so for every test a
        // fresh ClientBucket is created.
        $this->clientBucket = new ClientBucket();
    }

    public function testClientCreateAClientAndReturnsIt()
    {
        $this->assertInstanceOf('\\AlphaRPC\\Manager\\ClientHandler\\Client', $this->clientBucket->client(1));
    }

    public function testClientReturnsAnExistingClient()
    {
        $client = $this->clientBucket->client(1);
        $this->assertSame($client, $this->clientBucket->client(1));
    }

    public function testGetReturnsTheRequestedClient()
    {
        $id = 1;
        $client1 = $this->clientBucket->client($id);
        $client2 = $this->clientBucket->client($id+1);

        $this->assertSame($client1, $this->clientBucket->get($id));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Client with id 1 does not exist.
     */
    public function testGetThrowsExceptionForNonexistingClient()
    {
        $this->clientBucket->get(1);
    }
}
