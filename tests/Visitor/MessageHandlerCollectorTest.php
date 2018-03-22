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
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\MessageFlowAnalyzer\Visitor\CommandHandlerCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
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
}
