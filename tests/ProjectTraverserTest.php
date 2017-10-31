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
use Prooph\MessageFlowAnalyzer\Visitor\MessageHandlerCollector;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Listener\SendConfirmationEmail;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Command\AddIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserRegistered;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\ProcessManager\IdentityAdder;

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
                new MessageCollector(),
                new MessageHandlerCollector()
            ]
        );

        $msgFlow = $projectTraverser->traverse(__DIR__.'/Sample/DefaultProject');

        $this->assertEquals('default', $msgFlow->project());
        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR.'Sample'.DIRECTORY_SEPARATOR.'DefaultProject', $msgFlow->rootDir());

        $messageNames = array_keys($msgFlow->messages());
        sort($messageNames);

        $this->assertEquals([
            AddIdentity::class,
            ChangeUsername::class,
            RegisterUser::class,
            UserRegistered::class,
        ], $messageNames);

        $registerUser = $msgFlow->getMessage(RegisterUser::class);

        $this->assertEquals([
            RegisterUserHandler::class . '::__invoke'
        ], array_keys($registerUser->handlers()));

        $userRegistered = $msgFlow->getMessage(UserRegistered::class);

        $this->assertEquals([
            SendConfirmationEmail::class.'::onUserRegistered',
            IdentityAdder::class.'::'.'onUserRegistered',
        ], array_keys($userRegistered->handlers()));
    }
}