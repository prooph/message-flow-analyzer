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
use Prooph\MessageFlowAnalyzer\Visitor\MessageProducerCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Controller\UserController;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Command\AddIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\ProcessManager\IdentityAdder;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageProducerCollectorTest extends BaseTestCase
{
    /**
     * @var MessageProducerCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new MessageProducerCollector();
    }

    /**
     * @test
     */
    public function it_adds_producer_if_a_method_creates_a_message_using_new_class()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $identityAdder = ReflectionClass::createFromName(IdentityAdder::class);

        $msgFlow = $this->cut->onClassReflection($identityAdder, $msgFlow);

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(AddIdentity::class)));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(IdentityAdder::class.'::onUserRegistered')));
    }

    /**
     * @test
     */
    public function it_adds_producer_if_a_method_creates_a_message_using_named_constructor()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $userController = ReflectionClass::createFromName(UserController::class);

        $msgFlow = $this->cut->onClassReflection($userController, $msgFlow);

        //Uses self as return type of named constructor
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(RegisterUser::class)));
        //Uses message class as return type of named constructor
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(ChangeUsername::class)));

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(UserController::class.'::postAction')));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(UserController::class.'::patchAction')));
    }
}
