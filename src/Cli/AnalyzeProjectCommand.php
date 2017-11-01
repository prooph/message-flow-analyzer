<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Cli;

use Prooph\MessageFlowAnalyzer\Helper\ProjectTraverserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeProjectCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('project:analyze')
            ->setDescription('Analyzes message flow of project')
            ->addArgument('dir', InputArgument::OPTIONAL, 'The project directory', null)
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config', './prooph_analyzer.json')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file', './prooph_message_flow.json')
            ->setHelp(<<<EOT
The command analyzes prooph message flow of a project:

<info>%command.full_name% /path/to/project</info>
Specifiy project directory or omit argument to analyze current working dir.

<info>%command.full_name% --config /path/to/config.json /path/to/project</info>
If no <comment>--config</comment> option is provided a prooph_analyzer.json in the working dir is used.

<info>%command.full_name% --output /path/to/existing/target/dir/flow.json /path/to/project</info>
If no <comment>--output</comment> option is provided a prooph_message_flow.json is written to current working dir.
EOT
            );
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = $input->getArgument('dir');

        if(null === $rootDir) {
            $rootDir = getcwd();
        }

        $rootDir = realpath($rootDir);

        $output->writeln('Analyzing project dir ' . $rootDir);

        $configPath = $input->getOption('config');

        if(!file_exists($configPath)) {
            $output->writeln('<error>Config file '.$configPath.' not found.</error>');
            exit(1);
        }

        $config = json_decode(file_get_contents($configPath), true);

        $error = json_last_error();

        if($error !== JSON_ERROR_NONE) {
            $output->writeln('<error>Could not parse config file. Invalid JSON: '.json_last_error_msg().'</error>');
            exit($error);
        }

        $output->writeln('Using config ' . $configPath);

        $targetFile = $input->getOption('output');

        $traverser = ProjectTraverserFactory::buildTraverserFromConfig($config);

        $msgFlow = $traverser->traverse($rootDir);

        file_put_contents($targetFile, json_encode($msgFlow->toArray(), JSON_PRETTY_PRINT));

        $output->writeln('<info>Analysis written to '.$targetFile.'</info>');
        exit(0);
    }
}