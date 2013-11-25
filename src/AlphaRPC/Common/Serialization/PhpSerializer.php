<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */

namespace AlphaRPC\Common\Serialization;

/**
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Common
 */
class PhpSerializer implements SerializerInterface
{
    public function serialize($data)
    {
        return base64_encode(serialize($data));
    }

    public function unserialize($dataString)
    {
        $data = @unserialize(base64_decode($dataString));
        if ($data === false && $dataString != 'YjowOw==') {
            throw new \RuntimeException('Unable to decode: '.$dataString.'.');
        }

        return $data;
    }
}
