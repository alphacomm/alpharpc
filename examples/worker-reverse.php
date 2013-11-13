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

use AlphaRPC\Common\Logger\EchoLogger;
use AlphaRPC\Worker\Runner as WorkerRunner;
use AlphaRPC\Worker\Service;

$config = require __DIR__.'/config.php';

$worker = new WorkerRunner($config['worker-handler'], __DIR__.'/../ipc');
$worker->setLogger(new EchoLogger());
$worker->forkAndRunService(function(Service $service) {
    $service->addAction('reverse', function($param) {
        return strrev($param);
    });
});
$worker->run();
