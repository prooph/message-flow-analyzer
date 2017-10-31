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
use Prooph\MessageFlowAnalyzer\Visitor\MessageHandlerCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageHandlerCollectorTest extends BaseTestCase
{
    /**
     * @var MessageHandlerCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new MessageHandlerCollector();
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

        $this->assertTrue($msgFlow->knowsMessage(RegisterUser::class));

        $registerUser = $msgFlow->getMessage(RegisterUser::class);

        $handler = $registerUser->handlers()[RegisterUserHandler::class . '::__invoke'];

        $this->assertInstanceOf(MessageHandler::class, $handler);
    }

    /**
     * @test
     */
    public function it_adds_message_if_it_is_not_known_by_message_flow()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $handler = ReflectionClass::createFromName(RegisterUserHandler::class);

        $updatedMsgFlow = $this->cut->onClassReflection($handler, $msgFlow);

        $this->assertFalse($msgFlow->knowsMessage(RegisterUser::class));
        $this->assertTrue($updatedMsgFlow->knowsMessage(RegisterUser::class));

        $registerUser = $updatedMsgFlow->getMessage(RegisterUser::class);

        $handler = $registerUser->handlers()[RegisterUserHandler::class . '::__invoke'];

        $this->assertInstanceOf(MessageHandler::class, $handler);
    }
}