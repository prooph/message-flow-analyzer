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

namespace Prooph\MessageFlowAnalyzer\Visitor;

use PhpParser\Node as ParserNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\Helper\MessageClassProvider;
use Prooph\MessageFlowAnalyzer\Helper\MessageNameEqualsClassProvider;
use Prooph\MessageFlowAnalyzer\Helper\MessageProducingMethodScanner;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class MessagingCollector implements ClassVisitor
{
    use MessageProducingMethodScanner;

    private static $messageClassProvider;

    public static function useMessageClassProvider(MessageClassProvider $messageClassProvider): void
    {
        self::$messageClassProvider = $messageClassProvider;
    }

    public static function useDefaultMessageClassProvider(): void
    {
        self::$messageClassProvider = null;
    }

    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if ($reflectionClass->implementsInterface(ProophMsg::class)) {
            return $messageFlow;
        }

        if (MessageFlow\EventRecorder::isEventRecorder($reflectionClass)) {
            return $messageFlow;
        }

        $messageFactoryProperties = ScanHelper::findMessageFactoryProperties($reflectionClass);

        $methods = $reflectionClass->getMethods();

        $eventListeners = [];

        foreach ($methods as $method) {
            if ($method->getNumberOfParameters() === 1) {
                if ($eventListenerScan = $this->checkIsEventListener($messageFlow, $method)) {
                    [$node] = $eventListenerScan;
                    $eventListeners[$node->id()] = $eventListenerScan;
                }
            }
        }

        $messageProducers = $this->findMessageProducers($reflectionClass, $messageFlow, $messageFactoryProperties);
        $handledProducers = [];

        foreach ($eventListeners as [$node, $eventListener, $method, $event]) {
            /* @var $node MessageFlow\Node */
            /* @var $eventListener MessageFlow\MessageHandler */
            /* @var $method ReflectionMethod */
            /* @var $event MessageFlow\Message */

            if (! $messageFlow->knowsMessage($event)) {
                $messageFlow = $messageFlow->addMessage($event);
            }

            if (isset($messageProducers[$node->id()])) {
                [$producerNode, $producer, $producerMethod, $command] = $messageProducers[$node->id()];

                if (! $messageFlow->knowsMessage($command)) {
                    $messageFlow = $messageFlow->addMessage($command);
                }

                $messageFlow = $this->addProcessManager(
                    $messageFlow,
                    MessageFlow\NodeFactory::createMessageNode($event),
                    MessageFlow\NodeFactory::createMessageNode($command),
                    $producer
                );

                $handledProducers[] = $node->id();
                continue;
            }

            $invokedProducers = [];

            if (count($messageProducers) > 0) {
                $invokedProducers = $this->checkIfEventListenerInvokesMessageProducer($method, array_keys($messageProducers));
            }

            if (count($invokedProducers) === 0) {
                $messageFlow = $this->addEventListener(
                    $messageFlow,
                    MessageFlow\NodeFactory::createMessageNode($event),
                    MessageFlow\NodeFactory::createEventListenerNode($eventListener)
                );
            } else {
                foreach ($invokedProducers as $invokedProducerId) {
                    [$producerNode, $producer, $producerMethod, $command] = $messageProducers[$invokedProducerId];

                    if (! $messageFlow->knowsMessage($command)) {
                        $messageFlow = $messageFlow->addMessage($command);
                    }

                    $messageFlow = $this->addProcessManager(
                        $messageFlow,
                        MessageFlow\NodeFactory::createMessageNode($event),
                        MessageFlow\NodeFactory::createMessageNode($command),
                        $producer,
                        $method
                    );
                    $handledProducers[] = $invokedProducerId;
                }
            }
        }

        $unhandledProducers = array_diff(array_keys($messageProducers), $handledProducers);

        foreach ($unhandledProducers as $producerNodeId) {
            [$producerNode, $producer, $producerMethod, $command] = $messageProducers[$producerNodeId];

            if (! $messageFlow->knowsMessage($command)) {
                $messageFlow = $messageFlow->addMessage($command);
            }

            $messageProducerNode = MessageFlow\NodeFactory::createMessageProducingServiceNode($producer, $command);

            if (! $messageFlow->knowsNode($messageProducerNode)) {
                $messageFlow = $messageFlow->addNode($messageProducerNode);
            }

            $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($messageProducerNode->id(), Util::codeIdentifierToNodeId($command->name())));
        }

        return $messageFlow;
    }

    private function checkIsEventListener(MessageFlow $messageFlow, ReflectionMethod $method): ?array
    {
        $message = ScanHelper::checkIfMethodHandlesMessage($messageFlow, $method);

        if (! $message || $message->type() !== ProophMsg::TYPE_EVENT) {
            return null;
        }
        $eventListener = MessageFlow\MessageHandler::fromReflectionMethod($method);

        $node = MessageFlow\NodeFactory::createEventListenerNode($eventListener);

        return [$node, $eventListener, $method, $message];
    }

    private function findMessageProducers(ReflectionClass $reflectionClass, MessageFlow $messageFlow, array $messageFactoryProperties): array
    {
        $messageProducers = [];

        $this->checkMessageProduction(
            $messageFlow,
            $reflectionClass,
            function (MessageFlow $messageFlow, MessageFlow\Message $message, ReflectionMethod $method) use (&$messageProducers): MessageFlow {
                $messageProducer = MessageFlow\MessageProducer::fromReflectionMethod($method);

                $node = MessageFlow\NodeFactory::createMessageProducingServiceNode($messageProducer, $message);
                $messageProducers[$node->id()] = [$node, $messageProducer, $method, $message];

                return $messageFlow;
            },
            null,
            $this->getMessageClassProvider(),
            $messageFactoryProperties
        );

        return $messageProducers;
    }

    private function checkIfEventListenerInvokesMessageProducer(ReflectionMethod $eventListener, array $producerNodeIds): array
    {
        $nodeVisitor = new class($eventListener, $producerNodeIds) extends NodeVisitorAbstract {
            private $eventListener;
            private $producerNodeIds;
            private $invokedProducers = [];

            public function __construct(ReflectionMethod $eventListener, array $producerNodeIds)
            {
                $this->eventListener = $eventListener;
                $this->producerNodeIds = $producerNodeIds;
            }

            public function leaveNode(ParserNode $node)
            {
                if ($node instanceof ParserNode\Expr\MethodCall && $node->var->name === 'this') {
                    $methodNodeId = Util::codeIdentifierToNodeId(
                        $this->eventListener->getImplementingClass()->getName() . '::' . $node->name
                    );

                    if (in_array($methodNodeId, $this->producerNodeIds)) {
                        $this->invokedProducers[] = $methodNodeId;
                    }
                }
            }

            public function getInvokedProducers(): array
            {
                return $this->invokedProducers;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        $traverser->traverse($eventListener->getBodyAst());

        return $nodeVisitor->getInvokedProducers();
    }

    private function addProcessManager(
        MessageFlow $messageFlow,
        MessageFlow\Node $event,
        MessageFlow\Node $command,
        MessageFlow\MessageProducer $processManager,
        ReflectionMethod $listenerMethod = null
    ): MessageFlow {
        $pmNode = MessageFlow\NodeFactory::createProcessManagerNode($processManager);

        if (! $messageFlow->knowsNode($pmNode)) {
            $messageFlow = $messageFlow->addNode($pmNode);
        }

        if ($listenerMethod) {
            $listenerInvokingProducer = MessageFlow\MessageProducer::fromReflectionMethod($listenerMethod);

            $listenerInvokingProducerNode = MessageFlow\NodeFactory::createProcessManagerNode($listenerInvokingProducer);

            if (! $messageFlow->knowsNode($listenerInvokingProducerNode)) {
                $messageFlow = $messageFlow->addNode($listenerInvokingProducerNode);
            }

            $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($event->id(), $listenerInvokingProducerNode->id()));
            $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($listenerInvokingProducerNode->id(), $pmNode->id()));
        } else {
            $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($event->id(), $pmNode->id()));
        }

        $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($pmNode->id(), $command->id()));

        return $messageFlow;
    }

    private function addEventListener(MessageFlow $messageFlow, MessageFlow\Node $event, MessageFlow\Node $eventListener): MessageFlow
    {
        if (! $messageFlow->knowsNode($eventListener)) {
            $messageFlow = $messageFlow->addNode($eventListener);
        }

        $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($event->id(), $eventListener->id()));

        return $messageFlow;
    }

    private function getMessageClassProvider(): MessageClassProvider
    {
        if (null === self::$messageClassProvider) {
            self::$messageClassProvider = new MessageNameEqualsClassProvider();
        }

        return self::$messageClassProvider;
    }
}
