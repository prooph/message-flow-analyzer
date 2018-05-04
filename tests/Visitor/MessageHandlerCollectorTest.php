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

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow\Edge;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\MessageFlowAnalyzer\Visitor\CommandHandlerCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\AddUserIdentityHandler;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageHandlerCollectorTest extends BaseTestCase
{
    /**
     * @var CommandHandlerCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new CommandHandlerCollector();
    }

    /**
     * @test
     */
    public function it_adds_handler_to_message_if_message_is_argument_of_a_handler_method()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $msgFlow = $msgFlow->setMessage(Message::fromReflectionClass(ReflectionClass::createFromName(RegisterUser::class)));

        $handler = ReflectionClass::createFromName(RegisterUserHandler::class);

        $msgFlow = $this->cut->onClassReflection($handler, $msgFlow);

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(RegisterUserHandler::class . '::__invoke')));
    }

    /**
     * @test
     */
    public function it_connects_handler_with_event_recorder_factory()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $handler = ReflectionClass::createFromName(AddUserIdentityHandler::class);

        $msgFlow = $this->cut->onClassReflection($handler, $msgFlow);

        $edge = new Edge(
            Util::codeIdentifierToNodeId(AddUserIdentityHandler::class . '::__invoke'),
            Util::codeIdentifierToNodeId(User::class . '::addIdentity')
        );

        $this->assertArrayHasKey($edge->id(), $msgFlow->edges());
    }

    /**
     * @test
     */
    public function it_connects_handler_with_invoked_event_recorder()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $handler = ReflectionClass::createFromName(User\Command\DeactivateIdentityHandler::class);

        $msgFlow = $this->cut->onClassReflection($handler, $msgFlow);

        $edge = new Edge(
            Util::codeIdentifierToNodeId(User\Command\DeactivateIdentityHandler::class . '::__invoke'),
            Util::codeIdentifierToNodeId(Identity::class . '::deactivate')
        );

        $this->assertArrayHasKey($edge->id(), $msgFlow->edges());
    }
}
