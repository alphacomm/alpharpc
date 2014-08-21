<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license   BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Client\Protocol;

use AlphaRPC\Common\Socket\Message;

/**
 * Indicate that the Client Handler accepted the request.
 *
 * It may already contain the result of the request.
 *
 * @package AlphaRPC\Worker\Protocol
 */
class ExecuteResponse extends FetchResponse
{
    /**
     * Creates an ExecuteResponse.
     *
     * @param string $requestId
     * @param mixed  $result [OPTIONAL] The result of the request.
     */
    public function __construct($requestId, $result = null)
    {
        parent::__construct($requestId, $result);
    }

    /**
     * Creates an instance of this class based on the Message.
     *
     * @param Message $msg
     *
     * @return ExecuteResponse
     */
    public static function fromMessage(Message $msg)
    {
        $request_id = $msg->shift();
        $result     = $msg->count() ? $msg->shift() : null;

        return new self($request_id, $result);
    }
}
