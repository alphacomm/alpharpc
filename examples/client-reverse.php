#!/usr/bin/env php
<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Reen Lokum <reen@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Example
 */

require __DIR__.'/../vendor/autoload.php';

use AlphaRPC\Client\Client;

$config = require __DIR__.'/config.php';

$client = new Client();
$client->addManager($config['client-handler']);

if (!isset($_SERVER['argv'][1])) {
    echo 'You can provide an argument to reverse, using abc now.'.PHP_EOL.PHP_EOL;
    $param = 'abc';
} else {
    $param = $_SERVER['argv'][1];
}

echo '[DEBUG] Sending request to worker "reverse" with argument "'.$param.'"...'.PHP_EOL;
$result = $client->request('reverse', array($param));
echo '[DEBUG] Received a response: '.$result.PHP_EOL.PHP_EOL;

echo 'The reverse of "'.$param.'" is "'.$result.'".'.PHP_EOL;
