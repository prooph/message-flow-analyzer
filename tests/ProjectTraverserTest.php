<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer;

use Prooph\MessageFlowAnalyzer\Filter\ExcludeHiddenFileInfo;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeTestsDir;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeVendorDir;
use Prooph\MessageFlowAnalyzer\Filter\IncludePHPFile;
use Prooph\MessageFlowAnalyzer\ProjectTraverser;
use Prooph\MessageFlowAnalyzer\Visitor\EventRecorderCollector;
use Prooph\MessageFlowAnalyzer\Visitor\EventRecorderInvokerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageHandlerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageProducerCollector;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Controller\UserController;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Listener\SendConfirmationEmail;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Command\AddIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Event\IdentityAdded;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UsernameChanged;
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
                new ExcludeTestsDir(),
                new ExcludeHiddenFileInfo(),
                new IncludePHPFile(),
            ],
            [
                new MessageCollector(),
                new MessageHandlerCollector(),
                new MessageProducerCollector(),
                new EventRecorderCollector(),
                new EventRecorderInvokerCollector(),
            ]
        );

        $msgFlow = $projectTraverser->traverse(__DIR__.'/Sample/DefaultProject');

        $this->assertEquals('default', $msgFlow->project());
        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR.'Sample'.DIRECTORY_SEPARATOR.'DefaultProject', $msgFlow->rootDir());

        $messageNames = \array_keys($msgFlow->messages());
        \sort($messageNames);

        $this->assertEquals([
            AddIdentity::class,
            IdentityAdded::class,
            User\Command\AddUserIdentity::class,
            ChangeUsername::class,
            RegisterUser::class,
            UserRegistered::class,
            UsernameChanged::class,
        ], $messageNames);

        $registerUser = $msgFlow->getMessage(RegisterUser::class);

        $this->assertEquals([
            RegisterUserHandler::class . '::__invoke',
        ], \array_keys($registerUser->handlers()));

        $this->assertEquals([
            UserController::class . '::postAction',
        ], \array_keys($registerUser->producers()));

        $userRegistered = $msgFlow->getMessage(UserRegistered::class);

        $this->assertEquals([
            SendConfirmationEmail::class.'::onUserRegistered',
            IdentityAdder::class.'::'.'onUserRegistered',
        ], \array_keys($userRegistered->handlers()));

        $this->assertEquals([
            User::class.'::register',
        ], \array_keys($userRegistered->recorders()));

        $changeUsername = $msgFlow->getMessage(ChangeUsername::class);

        $this->assertEquals([
            UserController::class . '::patchAction',
        ], \array_keys($changeUsername->producers()));

        $addIdentity = $msgFlow->getMessage(AddIdentity::class);

        $this->assertEquals([
            IdentityAdder::class . '::onUserRegistered',
        ], \array_keys($addIdentity->producers()));

        $identityAdded = $msgFlow->getMessage(IdentityAdded::class);

        $this->assertEquals([
            Identity::class.'::add',
            Identity::class.'::addForUser',
        ], \array_keys($identityAdded->recorders()));

        $this->assertEquals([
            RegisterUserHandler::class.'::__invoke->'.User::class.'::register',
            User\Command\ChangeUsernameHandler::class.'::handle->'.User::class.'::changeUsername',
            User\Command\AddUserIdentityHandler::class.'::__invoke->'.User::class.'::addIdentity',
            User::class.'::addIdentity->'.Identity::class.'::add',
        ], \array_keys($msgFlow->eventRecorderInvokers()));
    }
}
