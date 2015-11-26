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

use AlphaRPC\Client\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Client provides the ability to inspect the Workers for this instance of AlphaRPC
 *
 * @author     Jacob Kiers <jacob@alphacomm.nl>
 * @package    AlphaRPC
 * @subpackage Console
 */
class Client extends Command
{
    /**
     * @var Container
     */
    protected $diContainer;

    public function __construct(Container $container)
    {
        parent::__construct('client');
        $this->diContainer = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setDescription('A quick-and-diry client for AlphaRPC');
        $this->getDefinition()->addOption(
            new InputOption(
                'no-wait',
                null,
                InputOption::VALUE_NONE,
                'Do not wait indefinitely for the result (makes a background request)'
            )
        );

        $this->getDefinition()->addOption(
            new InputOption(
                'fetch',
                'f',
                InputOption::VALUE_REQUIRED,
                'Fetch the result for the given request ID'
            )
        );
        $this->getDefinition()->addArgument(
            new InputArgument(
                'service',
                InputArgument::OPTIONAL,
                'The name of the service to call'
            )
        );

        $this->getDefinition()->addArgument(
            new InputArgument(
                'parameters',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'The parameters to call the service with'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument('service');
        $parameters = $input->getArgument('parameters');
        $fetch = $input->getOption('fetch');
        $wait = !$input->getOption('no-wait');

        if (!$fetch && !$service) {
            $output->writeln('<error>The "service" argument is required when the --fetch option is not given.</error>');

            return 1;
        }

        $client = new \AlphaRPC\Client\Client();
        $client->addManager($this->diContainer->getParameter('client_handler'));

        if (!$input->getOption('fetch')) {
            $request = $client->startRequest($service, $parameters);
        } else {
            $request = new Request($service, $parameters);
            $request->setRequestId($input->getOption('fetch'));
        }

        echo $client->fetchResponse($request, $wait);
    }
}
