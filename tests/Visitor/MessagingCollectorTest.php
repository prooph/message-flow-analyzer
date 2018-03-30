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
use Prooph\MessageFlowAnalyzer\MessageFlow\Node;
use Prooph\MessageFlowAnalyzer\Visitor\MessagingCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Controller\UserController;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Command\AddIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\DeactivateIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserDeactivated;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\ProcessManager\IdentityAdder;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\ProcessManager\SyncActiveStatus;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessagingCollectorTest extends BaseTestCase
{
    /**
     * @var MessagingCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new MessagingCollector();
    }

    /**
     * @test
     */
    public function it_adds_process_manager_if_a_method_creates_a_message_using_new_class_and_consumes_event()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $identityAdder = ReflectionClass::createFromName(IdentityAdder::class);

        $msgFlow = $this->cut->onClassReflection($identityAdder, $msgFlow);

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(AddIdentity::class)));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(IdentityAdder::class.'::onUserRegistered')));

        $node = $msgFlow->getNode(Util::codeIdentifierToNodeId(IdentityAdder::class.'::onUserRegistered'));

        $this->assertEquals(Node::TYPE_PROCESS_MANAGER, $node->type());
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

    /**
     * @test
     */
    public function it_adds_process_manager_if_event_consuming_method_invokes_command_producing_method()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $processManager = ReflectionClass::createFromName(SyncActiveStatus::class);

        $msgFlow = $this->cut->onClassReflection($processManager, $msgFlow);

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(SyncActiveStatus::class.'::onUserDeactivated')));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(SyncActiveStatus::class.'::deactivateIdentities')));

        $listenerNode = $msgFlow->getNode(Util::codeIdentifierToNodeId(SyncActiveStatus::class.'::onUserDeactivated'));
        $producerNode = $msgFlow->getNode(Util::codeIdentifierToNodeId(SyncActiveStatus::class.'::deactivateIdentities'));

        $this->assertEquals(Node::TYPE_PROCESS_MANAGER, $listenerNode->type());
        $this->assertEquals(Node::TYPE_PROCESS_MANAGER, $producerNode->type());

        $this->assertArrayHasKey(
            (new Edge(
                Util::codeIdentifierToNodeId(UserDeactivated::class),
                Util::codeIdentifierToNodeId(SyncActiveStatus::class . '::onUserDeactivated')
            ))->id(),
            $msgFlow->edges()
        );

        $this->assertArrayHasKey(
            (new Edge(
                Util::codeIdentifierToNodeId(SyncActiveStatus::class . '::onUserDeactivated'),
                Util::codeIdentifierToNodeId(SyncActiveStatus::class . '::deactivateIdentities')
            ))->id(),
            $msgFlow->edges()
        );

        $this->assertArrayHasKey(
            (new Edge(
                Util::codeIdentifierToNodeId(SyncActiveStatus::class . '::deactivateIdentities'),
                Util::codeIdentifierToNodeId(DeactivateIdentity::class)
            ))->id(),
            $msgFlow->edges()
        );
    }
}
