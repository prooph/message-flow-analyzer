<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\MessageFlowAnalyzer\MessageFlow\MessageHandler;
use Prooph\MessageFlowAnalyzer\Visitor\EventRecorderInvokerCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use Roave\BetterReflection\Reflection\ReflectionClass;

class EventRecorderInvokerCollectorTest extends BaseTestCase
{
    /**
     * @var EventRecorderInvokerCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new EventRecorderInvokerCollector();
    }

    /**
     * @test
     */
    public function it_identifies_event_recorder_static_method_invoked_by_command_handler()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $registerUser = Message::fromReflectionClass(ReflectionClass::createFromName(RegisterUser::class));

        $registerUserHandler = ReflectionClass::createFromName(RegisterUserHandler::class);

        $registerUser = $registerUser->addHandler(MessageHandler::fromReflectionMethod(
            $registerUserHandler->getMethod('__invoke')
        ));

        $msgFlow = $msgFlow->setMessage($registerUser);

        $msgFlow = $this->cut->onClassReflection($registerUserHandler, $msgFlow);

        $this->assertEquals([
            RegisterUserHandler::class.'::__invoke->'.User::class.'::register',
        ], array_keys($msgFlow->eventRecorderInvokers()));
    }

    /**
     * @test
     */
    public function it_identifies_event_recorder_method_invoked_by_command_handler()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $changeUsername = Message::fromReflectionClass(ReflectionClass::createFromName(User\Command\ChangeUsername::class));

        $changeUsernameHandler = ReflectionClass::createFromName(User\Command\ChangeUsernameHandler::class);

        $changeUsername = $changeUsername->addHandler(MessageHandler::fromReflectionMethod(
            $changeUsernameHandler->getMethod('handle')
        ));

        $msgFlow = $msgFlow->setMessage($changeUsername);

        $msgFlow = $this->cut->onClassReflection($changeUsernameHandler, $msgFlow);

        $this->assertEquals([
            User\Command\ChangeUsernameHandler::class.'::handle->'.User::class.'::changeUsername',
        ], array_keys($msgFlow->eventRecorderInvokers()));
    }

    /**
     * @test
     */
    public function it_identifies_event_recorder_method_used_as_factory_for_another_event_recorder()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $addUserIdentity = Message::fromReflectionClass(ReflectionClass::createFromName(User\Command\AddUserIdentity::class));

        $addUserIdentityHandler = ReflectionClass::createFromName(User\Command\AddUserIdentityHandler::class);

        $addUserIdentity = $addUserIdentity->addHandler(MessageHandler::fromReflectionMethod(
            $addUserIdentityHandler->getMethod('__invoke')
        ));

        $msgFlow = $msgFlow->setMessage($addUserIdentity);

        $msgFlow = $this->cut->onClassReflection($addUserIdentityHandler, $msgFlow);

        $this->assertEquals([
            User\Command\AddUserIdentityHandler::class.'::__invoke->'.User::class.'::addIdentity',
            User::class.'::addIdentity->'.Identity::class.'::add',
        ], array_keys($msgFlow->eventRecorderInvokers()));
    }
}
