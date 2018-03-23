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

namespace ProophTest\MessageFlowAnalyzer;

use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\MessageFlowAnalyzer\MessageFlow\MessageHandler;
use Prooph\MessageFlowAnalyzer\MessageFlow\NodeFactory;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsernameHandler;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserRegistered;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageFlowTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_filters_command_handlers()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $registerUser = Message::fromReflectionClass(ReflectionClass::createFromName(RegisterUser::class));
        $changeUsername = Message::fromReflectionClass(ReflectionClass::createFromName(ChangeUsername::class));
        $userRegistered = Message::fromReflectionClass(ReflectionClass::createFromName(UserRegistered::class));

        $registerUserHandlerNode = NodeFactory::createCommandHandlerNode(
            MessageHandler::fromReflectionMethod(
                ReflectionClass::createFromName(RegisterUserHandler::class)->getMethod('__invoke')
            )
        );

        $changeUsernameHandlerNode = NodeFactory::createCommandHandlerNode(
            MessageHandler::fromReflectionMethod(
                ReflectionClass::createFromName(ChangeUsernameHandler::class)->getMethod('handle')
            )
        );

        $msgFlow = $msgFlow->addNode($registerUserHandlerNode);

        $msgFlow = $msgFlow->setMessage($registerUser);

        $this->assertEquals([
            RegisterUserHandler::class,
        ], $msgFlow->getKnownCommandHandlers());

        $msgFlow = $msgFlow->setMessage($changeUsername);
        $msgFlow = $msgFlow->addNode($changeUsernameHandlerNode);

        $this->assertEquals([
            RegisterUserHandler::class,
            ChangeUsernameHandler::class,
        ], $msgFlow->getKnownCommandHandlers());

        $msgFlow = $msgFlow->setMessage($userRegistered);

        $this->assertEquals([
            RegisterUserHandler::class,
            ChangeUsernameHandler::class,
        ], $msgFlow->getKnownCommandHandlers());
    }
}
