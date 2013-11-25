<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */

namespace AlphaRPC\Manager\ClientHandler;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage ClientHandler
 */
class ClientBucket
{
    /**
     * Array that keeps track of all the clients.
     *
     * @var AlphaRPC\Manager\ClientHandler\Client[]
     */
    protected $clients = array();

    /**
     * Array that keeps track of the clients per request.
     *
     * @var AlphaRPC\Manager\ClientHandler\Client[][]
     */
    protected $requests = array();

    /**
     * Creates an instance of client.
     *
     * @param string $id
     *
     * @return \AlphaRPC\Manager\ClientHandler\Client
     */
    public function client($id)
    {
        if (isset($this->clients[$id])) {
            return $this->clients[$id];
        }

        return new Client($this, $id);
    }

    /**
     * Removes and re-adds the client to the bucket. This way
     * the "requests" array does not get outdated and the clients,
     * are sorted by time.
     *
     * @param AlphaRPC\Manager\ClientHandler\Client $client
     *
     * @return \AlphaRPC\Manager\ClientHandler\ClientBucket
     */
    public function refresh(Client $client)
    {
        $clientId = $client->getId();
        $this->clients[$clientId] = $client;
        $this->removeClientForRequest($clientId, $client->getPreviousRequest());

        $requestId = $client->getRequest();
        if ($requestId  !== null) {
            if (!isset($this->requests[$requestId])) {
                $this->requests[$requestId] = array();
            }
            $this->requests[$requestId][$clientId] = $client;
            $this->clientRequest[$clientId] = $requestId;
        }

        return $this;
    }

    /**
     * Removes the client from the "clients" and the "requests"
     * array.
     *
     * @param AlphaRPC\Manager\ClientHandler\Client $client
     *
     * @return \AlphaRPC\Manager\ClientHandler\ClientBucket
     */
    public function remove(Client $client)
    {
        $clientId = $client->getId();
        if (!isset($this->clients[$clientId])) {
            return $this;
        }

        $requestId = $client->getRequest();
        $this->removeClientForRequest($clientId, $requestId);
        unset($this->clients[$clientId]);

        return $this;
    }

    /**
     *
     * @param string $clientId
     * @param string $requestId
     */
    protected function removeClientForRequest($clientId, $requestId)
    {
        if ($requestId === null || !isset($this->requests[$requestId])) {
            return;
        }
        if (isset($this->requests[$requestId][$clientId])) {
            unset($this->requests[$requestId][$clientId]);
            if (count($this->requests[$requestId]) <= 0) {
                unset($this->requests[$requestId]);
            }
        }
    }

    /**
     * Gets the clients awaiting a result for a request.
     *
     * @param string $requestId
     *
     * @return \AlphaRPC\Manager\ClientHandler\Client[]
     */
    public function getClientsForRequest($requestId)
    {
        if (!isset($this->requests[$requestId])) {
            return array();
        }

        return $this->requests[$requestId];
    }

    /**
     * Gets a single client by id.
     *
     * @param string $clientId
     *
     * @return AlphaRPC\Manager\ClientHandler\Client
     * @throws \RuntimeException
     */
    public function get($clientId)
    {
        if (!isset($this->clients[$clientId])) {
            throw new \RuntimeException('Client with id '.$clientId.' does not exist.');
        }

        return $this->clients[$clientId];
    }

    /**
     * Gets all expired clients based on a timeout in microseconds.
     *
     * @param int $timeout
     *
     * @return \AlphaRPC\Manager\ClientHandler\Client[]
     */
    public function getExpired($timeout)
    {
        $expired = array();
        $timeoutAt = microtime(true) - ($timeout / 1000);
        foreach ($this->clients as $client) {
            if ($client->getTime() > $timeoutAt) {
                // Clients are ordered by time.
                break;
            }
            $expired[] = $client;
        }

        return $expired;
    }

}
