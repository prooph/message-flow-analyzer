<?php

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Cli;

use Prooph\MessageFlowAnalyzer\Helper\ProjectTraverserFactory;
use Prooph\MessageFlowAnalyzer\MessageFlow\EventRecorder;
use Prooph\MessageFlowAnalyzer\MessageFlow\NodeFactory;
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
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'JsonPrettyPrint')
            ->setHelp(<<<EOT
The command analyzes prooph message flow of a project:

<info>%command.full_name% /path/to/project</info>
Specifiy project directory or omit argument to analyze current working dir.

<info>%command.full_name% --config /path/to/config.json /path/to/project</info>
If no <comment>--config</comment> option is provided a prooph_analyzer.json in the working dir is used.

<info>%command.full_name% --output /path/to/existing/target/dir/flow.json /path/to/project</info>
If no <comment>--output</comment> option is provided a prooph_message_flow.json is written to current working dir.

<info>%command.full_name% --format JsonArangoGraphNodes</info>
Specify format of the output - JsonPrettyPrint by default.
EOT
            );
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = $input->getArgument('dir');

        if (null === $rootDir) {
            $rootDir = getcwd();
        }

        $rootDir = realpath($rootDir);

        $output->writeln('Analyzing project dir ' . $rootDir);

        $configPath = $input->getOption('config');

        if (! file_exists($configPath)) {
            $output->writeln('<error>Config file '.$configPath.' not found.</error>');
            exit(1);
        }

        $config = json_decode(file_get_contents($configPath), true);

        $error = json_last_error();

        if ($error !== JSON_ERROR_NONE) {
            $output->writeln('<error>Could not parse config file. Invalid JSON: '.json_last_error_msg().'</error>');
            exit($error);
        }

        $output->writeln('Using config ' . $configPath);

        $targetFile = $input->getOption('output');
        $formatterName = $input->getOption('format');

        if (isset($config['nodeClass'])) {
            NodeFactory::useNodeClass($config['nodeClass']);
        }

        if (isset($config['eventRecorderCheck'])) {
            EventRecorder::useEventRecorderCheckFunction($config['eventRecorderCheck']);
        }

        $traverser = ProjectTraverserFactory::buildTraverserFromConfig($config);
        $finalizers = ProjectTraverserFactory::buildFinalizersFromConfig($config);
        $formatter = ProjectTraverserFactory::buildOutputFormatter($formatterName);

        $msgFlow = $traverser->traverse($rootDir);

        foreach ($finalizers as $finalizer) {
            $msgFlow = $finalizer->finalize($msgFlow);
        }

        file_put_contents($targetFile, $formatter->messageFlowToString($msgFlow));

        $output->writeln('<info>Analysis written to '.$targetFile.'</info> using format: ' . $formatterName);
        exit(0);
    }
}
