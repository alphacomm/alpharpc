<?php
/**
 * This file is part of AlphaRPC (http://alphacomm.github.io/alpharpc/)
 *
 * @license BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Console;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct($cwd, $container)
    {
        parent::__construct('AlphaRPC', '0.1');
        $this->add(new Command\PrepareCustomConfig($cwd));
        $this->add(new Command\GenerateSupervisorConfig($cwd));
        $this->add(new Command\WorkerStatus($container));
        $this->add(new Command\QueueStatus($container));
    }
}
