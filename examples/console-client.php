#!/usr/bin/env php
<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author Jacob Kiers <jacob@alphacomm.nl>
 * @package AlphaRPC
 * @subpackage Example
 */

if (!isset($_SERVER['argv'][1])) {
    echo 'Usage:   '.$_SERVER['argv'][0] . ' <command> [argument] [argument] [...]'.PHP_EOL;
    echo 'Example: '.$_SERVER['argv'][0] . ' reverse abcdef'.PHP_EOL;
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

use AlphaRPC\Client\Client;

$config = require __DIR__.'/config.php';

$client = new Client();
$client->addManager($config['client-handler']);

$command = $_SERVER['argv'][1];
$arguments = array_slice($_SERVER['argv'], 2);

echo 'Requesting '.$command.' with arguments: '.print_r($arguments, true).PHP_EOL;

$client->setTimeout(2000);
$return = $client->request($command, $arguments);

echo 'Return data: '.PHP_EOL;
var_dump($return);
echo PHP_EOL;
