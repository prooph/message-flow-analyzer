<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer;

use Prooph\MessageFlowAnalyzer\Filter\ExcludeHiddenFileInfo;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeVendorDir;
use Prooph\MessageFlowAnalyzer\Filter\IncludePHPFile;
use Prooph\MessageFlowAnalyzer\ProjectTraverser;
use Prooph\MessageFlowAnalyzer\Visitor\MessageCollector;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserRegistered;

class ProjectTraverserTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_collects_message_flow()
    {
        $projectTraverser = new ProjectTraverser(
            'default',
            [
                new ExcludeVendorDir(),
                new ExcludeHiddenFileInfo(),
                new IncludePHPFile()
            ],
            [
                new MessageCollector()
            ]
        );

        $msgFlow = $projectTraverser->traverse(__DIR__.'/Sample/DefaultProject');

        $this->assertEquals('default', $msgFlow->project());
        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR.'Sample'.DIRECTORY_SEPARATOR.'DefaultProject', $msgFlow->rootDir());
        $this->assertEquals([
            RegisterUser::class,
            UserRegistered::class,
        ], array_keys($msgFlow->messages()));
    }
}