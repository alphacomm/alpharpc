<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license    BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright  Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 *
 * @author     Reen Lokum <reen@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage WorkerHandler
 */

namespace AlphaRPC\Console\Command;

use Symfony\Component\Console\Command\Command;
use AlphaRPC\Client\AlphaRPCStatus;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * QueueStatus provides the ability to inspect the Workers for this instance of AlphaRPC
 *
 * @author     Jacob Kiers <jacob@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Console
 */
class QueueStatus extends Command
{
    /**
     * @var Container
     */
    protected $diContainer;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(Container $container)
    {
        parent::__construct('queue-status');
        $this->diContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->output->writeln('');

        try {
            $this->row('Action', 'Queue', 'Busy', 'Avail', 'Info');

            $queueStatus = $this->getStatus();

            foreach ($queueStatus as $action) {
                $info = '';
                if ($action['available'] < 1) {
                    $info = '!';
                } else {
                    $ratio = $action['queue'] / $action['available'];
                    if ($ratio > 50) {
                        $info = '!';
                    } else {
                        if ($ratio > 25) {
                            $info = '?';
                        }
                    }
                }

                $this->row($action['action'], $action['queue'], $action['busy'], $action['available'], $info);
            }

        } catch (\Exception $e) {
            $this->output->writeln('<error>An error occured: '.$e->getMessage().'</error>');
        }

    }

    /**
     * Returns the queue status from the Worker Handler.
     *
     * @return array
     */
    protected function getStatus()
    {
        $worker = new AlphaRPCStatus();
        return $worker->queueStatus($this->diContainer->getParameter('worker_handler'));
    }

    /**
     * Print a single line with status information.
     *
     * @param $worker
     * @param $service
     * @param $current
     * @param $ready
     * @param $valid
     */
    protected function row($worker, $service, $current, $ready, $valid)
    {
        $mask = "| %-50.50s | %5s | %5s | %5s | %-4s |";
        $text = sprintf($mask, $worker, $service, $current, $ready, $valid);
        $this->output->writeln($text);
    }
}