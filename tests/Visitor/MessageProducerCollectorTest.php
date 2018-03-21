<?php

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\MessageFlow\MessageProducer;
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

        $this->assertTrue($msgFlow->knowsMessage(AddIdentity::class));

        $addIdentity = $msgFlow->getMessage(AddIdentity::class);

        $producer = $addIdentity->producers()[IdentityAdder::class.'::onUserRegistered'] ?? null;

        $this->assertInstanceOf(MessageProducer::class, $producer);
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
        $this->assertTrue($msgFlow->knowsMessage(RegisterUser::class));
        //Uses message class as return type of named constructor
        $this->assertTrue($msgFlow->knowsMessage(ChangeUsername::class));

        $registerUser = $msgFlow->getMessage(RegisterUser::class);
        $producer = $registerUser->producers()[UserController::class.'::postAction'] ?? null;
        $this->assertInstanceOf(MessageProducer::class, $producer);

        $changeUsername = $msgFlow->getMessage(ChangeUsername::class);
        $producer = $changeUsername->producers()[UserController::class.'::patchAction'] ?? null;
        $this->assertInstanceOf(MessageProducer::class, $producer);
    }
}
