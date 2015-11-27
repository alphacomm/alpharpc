<?php
/**
 * This file is part of AlphaRPC (http://alpharpc.net/)
 *
 * @license   BSD-3 (please see the LICENSE file distributed with this source code.
 * @copyright Copyright (c) 2010-2013, Alphacomm Group B.V. (http://www.alphacomm.nl/)
 */

namespace AlphaRPC\Console\Command;

use AlphaRPC\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCustomConfig extends Command
{

    /**
     * Path where alpharpc is installed.
     *
     * @var string
     */
    protected $installPath;

    /**
     * Working directory. When running binaries you are expected to have this
     * directory active.
     *
     * @var string
     */
    protected $workingDirectory;

    /**
     *
     * @param string $workingDirectory
     * @param string $installPath
     *
     * @throws RuntimeException
     */
    public function __construct($workingDirectory = null, $installPath = null)
    {
        parent::__construct('prepare-custom-config');
        $this->workingDirectory = $workingDirectory ?: getcwd();
        if (!is_dir($this->workingDirectory)) {
            throw new RuntimeException('Working directory does not exist.');
        }

        $this->installPath = $installPath ?: $this->getInstallPath();
        if (!is_dir($this->installPath)) {
            throw new RuntimeException('Install path does not exist.');
        }
    }

    public function configure()
    {
        $this->getDefinition()
            ->addOption(new InputOption(
                'custom-resources', 'r', InputOption::VALUE_NONE,
                '[DEPRECATED] Also define custom resources, logger for example.'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->copy('alpharpc_config.yml', $output);
        $this->copy('alpharpc_resources.yml', $output);

        $output->writeln(array(
            'Done.',
            '<info>You can now edit alpharpc_*.yml config files.</info>',
            '',
        ));
    }

    private function copy($file, $output)
    {
        $source = $this->installPath.'/app/config/'.$file;
        $target = $this->workingDirectory.'/'.$file;

        if (!file_exists($source)) {
            throw new RuntimeException('Unable to export, source "'.$source.'" does not exist.');
        }

        if (file_exists($target)) {
            $output->writeln(array(
                '<info>The target file "'.$target.'" already exists.</info>',
                '',
            ));

            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $dialog = $this->getHelper('dialog');
            $answer = $dialog->ask($output, '<question>Replace existing '.$file.'? [Y/n]</question> ', 'n');
            if ($answer != 'Y') {
                $output->writeln(array(
                    '',
                    '<info>File "'.$file.'" was not deployed.</info>',
                ));

                return;
            }
        }

        $output->writeln(array('Copying '.$file.' to '.$target));

        if (!copy($source, $target)) {
            throw new RuntimeException('Unable to copy "'.$file.'" to "'
                .$target.'".');
        }
    }

    private function getInstallPath()
    {
        $pos = strrpos(__DIR__, '/src/');
        if ($pos === false) {
            throw new RuntimeException('Install path could not be located.');
        }

        return substr(__DIR__, 0, $pos);
    }
}
