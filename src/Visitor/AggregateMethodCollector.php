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

use Prooph\MessageFlowAnalyzer\Helper\MessageProducingMethodScanner;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class AggregateMethodCollector implements ClassVisitor
{
    use MessageProducingMethodScanner;

    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if (! MessageFlow\EventRecorder::isEventRecorder($reflectionClass)) {
            return $messageFlow;
        }

        return $this->checkMessageProduction(
            $messageFlow,
            $reflectionClass,
            function (MessageFlow $messageFlow, MessageFlow\Message $message, ReflectionMethod $method): MessageFlow {
                $msgNode = MessageFlow\NodeFactory::createMessageNode($message);

                if (! $messageFlow->knowsNode($msgNode)) {
                    $messageFlow = $messageFlow->addMessage($message);
                }

                $eventRecorder = MessageFlow\EventRecorder::fromReflectionMethod($method);

                $eventRecorderNode = MessageFlow\NodeFactory::createEventRecordingAggregateMethodNode($eventRecorder);

                if (! $messageFlow->knowsNode($eventRecorderNode)) {
                    $messageFlow = $messageFlow->addNode($eventRecorderNode);
                }

                if ($eventRecorder->isClass() && ! $messageFlow->knowsNodeWithId(Util::codeIdentifierToNodeId($eventRecorder->class()))) {
                    $messageFlow = $messageFlow->addNode(MessageFlow\NodeFactory::createAggregateNode($eventRecorder));
                }

                $messageFlow = $messageFlow->addEdge(new MessageFlow\Edge($eventRecorderNode->id(), $msgNode->id()));

                $invokedEventRecorders = ScanHelper::checkIfEventRecorderMethodCallsOtherEventRecorders($eventRecorder);

                foreach ($invokedEventRecorders as $invokedEventRecorder) {
                    $messageFlow = $messageFlow->addEdge(new MessageFlow\Edge(
                        Util::codeIdentifierToNodeId($eventRecorder->identifier()),
                        Util::codeIdentifierToNodeId($invokedEventRecorder->identifier()))
                    );
                }

                return $messageFlow;
            },
            function (MessageFlow $messageFlow, ReflectionMethod $method): MessageFlow {
                $eventRecorder = MessageFlow\EventRecorder::fromReflectionMethod($method);

                $builtEventRecorder = ScanHelper::checkIfEventRecorderMethodIsUsedAsFactory($eventRecorder);

                if ($builtEventRecorder) {
                    $aggregateFactoryMethodNode = MessageFlow\NodeFactory::createAggregateFactoryMethodNode($eventRecorder);

                    if (! $messageFlow->knowsNode($aggregateFactoryMethodNode)) {
                        $messageFlow = $messageFlow->addNode($aggregateFactoryMethodNode);
                    }

                    $builtEventRecorderNode = MessageFlow\NodeFactory::createEventRecordingAggregateMethodNode($builtEventRecorder);
                    $messageFlow = $messageFlow->addEdge(new MessageFlow\Edge($aggregateFactoryMethodNode->id(), $builtEventRecorderNode->id()));
                }

                return $messageFlow;
            }
        );
    }
}
