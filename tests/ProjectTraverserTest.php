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

use Prooph\MessageFlowAnalyzer\Filter\ExcludeHiddenFileInfo;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeTestsDir;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeVendorDir;
use Prooph\MessageFlowAnalyzer\Filter\IncludePHPFile;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow\Edge;
use Prooph\MessageFlowAnalyzer\MessageFlow\Node;
use Prooph\MessageFlowAnalyzer\ProjectTraverser;
use Prooph\MessageFlowAnalyzer\Visitor\AggregateMethodCollector;
use Prooph\MessageFlowAnalyzer\Visitor\CommandHandlerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\EventListenerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageCollector;
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
                new CommandHandlerCollector(),
                new MessageProducerCollector(),
                new AggregateMethodCollector(),
                new EventListenerCollector(),
            ]
        );

        $msgFlow = $projectTraverser->traverse(__DIR__ . '/Sample/DefaultProject');

        $this->assertEquals('default', $msgFlow->project());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'Sample' . DIRECTORY_SEPARATOR . 'DefaultProject', $msgFlow->rootDir());

        $expectedNodes = [
            Util::codeIdentifierToNodeId(AddIdentity::class) => [Node::TYPE_COMMAND, AddIdentity::class],
            Util::codeIdentifierToNodeId(IdentityAdded::class) => [Node::TYPE_EVENT, IdentityAdded::class],
            Util::codeIdentifierToNodeId(User\Command\AddUserIdentity::class) => [Node::TYPE_COMMAND, User\Command\AddUserIdentity::class],
            Util::codeIdentifierToNodeId(ChangeUsername::class) => [Node::TYPE_COMMAND, ChangeUsername::class],
            Util::codeIdentifierToNodeId(RegisterUser::class) => [Node::TYPE_COMMAND, RegisterUser::class],
            Util::codeIdentifierToNodeId(UserRegistered::class) => [Node::TYPE_EVENT, UserRegistered::class],
            Util::codeIdentifierToNodeId(User\Event\UserActivated::class) => [Node::TYPE_EVENT, UserRegistered::class],
            Util::codeIdentifierToNodeId(RegisterUserHandler::class . '::__invoke') => [Node::TYPE_HANDLER, RegisterUserHandler::class . '::__invoke'],
            Util::codeIdentifierToNodeId(UserController::class . '::postAction') => [Node::TYPE_SERVICE, UserController::class . '::postAction'],
            Util::codeIdentifierToNodeId(UserController::class . '::patchAction') => [Node::TYPE_SERVICE, UserController::class . '::patchAction'],
            Util::codeIdentifierToNodeId(SendConfirmationEmail::class.'::onUserRegistered') => [Node::TYPE_LISTENER, SendConfirmationEmail::class.'::onUserRegistered'],
            Util::codeIdentifierToNodeId(User::class . '::register') => [Node::TYPE_AGGREGATE, User::class . '::register'],
            Util::codeIdentifierToNodeId(User::class . '::activate') => [Node::TYPE_AGGREGATE, User::class . '::activate'],
            Util::codeIdentifierToNodeId(User::class) => [Node::TYPE_AGGREGATE, User::class],
            Util::codeIdentifierToNodeId(IdentityAdder::class . '::onUserRegistered') => [Node::TYPE_PROCESS_MANAGER, IdentityAdder::class . '::onUserRegistered'],
            Util::codeIdentifierToNodeId(Identity::class . '::add') => [Node::TYPE_AGGREGATE, Identity::class . '::add'],
            Util::codeIdentifierToNodeId(Identity::class . '::addForUser') => [Node::TYPE_AGGREGATE, Identity::class . '::addForUser'],
            Util::codeIdentifierToNodeId(Identity::class) => [Node::TYPE_AGGREGATE, Identity::class],
        ];

        $nodes = $msgFlow->nodes();

        foreach ($expectedNodes as $nodeId => [$nodeType, $codeIdentifier]) {
            $this->assertTrue(array_key_exists($nodeId, $nodes), "Missing node for $codeIdentifier");

            $this->assertEquals($nodeType, $nodes[$nodeId]->type(), "Wrong node type for $codeIdentifier");
        }

        $expectedEdges = [
            (new Edge(
                Util::codeIdentifierToNodeId(RegisterUserHandler::class . '::__invoke'),
                Util::codeIdentifierToNodeId(User::class . '::register')
            ))->id() => [RegisterUserHandler::class . '::__invoke', User::class . '::register'],
            (new Edge(
                Util::codeIdentifierToNodeId(User\Command\ChangeUsernameHandler::class . '::handle'),
                Util::codeIdentifierToNodeId(User::class . '::changeUsername')
            ))->id() => [User\Command\ChangeUsernameHandler::class . '::handle', User::class . '::changeUsername'],
            (new Edge(
                Util::codeIdentifierToNodeId(User\Command\AddUserIdentityHandler::class . '::__invoke'),
                Util::codeIdentifierToNodeId(User::class . '::addIdentity')
            ))->id() => [User\Command\AddUserIdentityHandler::class . '::__invoke', User::class . '::addIdentity'],
            (new Edge(
                Util::codeIdentifierToNodeId(User::class . '::addIdentity'),
                Util::codeIdentifierToNodeId(Identity::class . '::add')
            ))->id() => [User::class . '::addIdentity', Identity::class . '::add'],
            (new Edge(
                Util::codeIdentifierToNodeId(User::class . '::register'),
                Util::codeIdentifierToNodeId(User::class . '::activate')
            ))->id() => [User::class . '::register', User::class . '::activate'],
        ];

        $edges = $msgFlow->edges();

        foreach ($expectedEdges as $edgeId => [$sourceIdentifier, $targetIdentifier]) {
            $this->assertTrue(array_key_exists($edgeId, $edges), "Missing edge $sourceIdentifier -> $targetIdentifier");
        }
    }
}
