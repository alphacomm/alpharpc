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
 * WorkerStatus provides the ability to inspect the Workers for this instance of AlphaRPC
 *
 * @author     Jacob Kiers <jacob@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Console
 */
class WorkerStatus extends Command
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
        parent::__construct('worker-status');
        $this->diContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->getDefinition()->addOption(
            new InputOption(
                'full',
                'f',
                InputOption::VALUE_NONE,
                'Full output: lists all actions, with their full names.'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $full = $input->getOption('full');
        $this->output = $output;

        $this->output->writeln('');

        try {
            $status = $this->getStatus();
            $this->row('Worker', 'Service', 'Current Service', 'Ready', 'Valid');
            foreach ($status as $worker) {
                $actions = $worker['actionList'];
                $this->row(
                    bin2hex($worker['id']),
                    array_shift($actions),
                    $worker['current'],
                    $worker['ready'] ? 'yes' : 'no',
                    $worker['valid'] ? 'yes' : 'no'
                );
                $i = 0;
                while (count($actions) > 0 && ($i < 3 || $full)) {
                    $i++;
                    $this->row('', array_shift($actions), '', '', '');
                }

                if (count($actions) > 0) {
                    $this->row('', '... and '.count($actions).' other services.', '', '', '');
                }
            }
        } catch (\Exception $e) {
            $this->output->writeln('<error>An error occured: '.$e->getMessage().'</error>');
        }

    }

    /**
     * Returns the worker status from the Worker Handler.
     *
     * @return array
     */
    protected function getStatus()
    {
        $worker = new AlphaRPCStatus();
        return $worker->workerStatus($this->diContainer->getParameter('worker_handler'));
    }

    /**
     * Print a single line with information.
     *
     * @param $worker
     * @param $service
     * @param $current
     * @param $ready
     * @param $valid
     */
    protected function row($worker, $service, $current, $ready, $valid)
    {
        $mask = "| %-10.10s | %-40.40s | %-40.40s | %-5s | %-5s |";
        $text = sprintf($mask, $worker, $service, $current, $ready, $valid);
        $this->output->writeln($text);
    }
}