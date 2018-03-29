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

use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\Helper\MessageProducingMethodScanner;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class MessageProducerCollector implements ClassVisitor
{
    use MessageProducingMethodScanner;

    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if ($reflectionClass->implementsInterface(ProophMsg::class)) {
            return $messageFlow;
        }

        if (MessageFlow\EventRecorder::isEventRecorder($reflectionClass)) {
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

                $receivedMsg = ScanHelper::checkIfMethodHandlesMessage($messageFlow, $method);

                $messageProducer = MessageFlow\MessageProducer::fromReflectionMethod($method);

                //process manager or saga that receives event and produces command
                if ($receivedMsg) {
                    //@TODO: Can we identify a Saga here?
                    $pmNode = MessageFlow\NodeFactory::createProcessManagerNode($messageProducer);

                    if (! $messageFlow->knowsNode($pmNode)) {
                        $messageFlow = $messageFlow->addNode($pmNode);
                    }

                    $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge(Util::codeIdentifierToNodeId($receivedMsg->name()), $pmNode->id()));
                    $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($pmNode->id(), $msgNode->id()));
                } else {
                    $messageProducerNode = MessageFlow\NodeFactory::createMessageProducingServiceNode($messageProducer, $message);

                    if (! $messageFlow->knowsNode($messageProducerNode)) {
                        $messageFlow = $messageFlow->addNode($messageProducerNode);
                    }

                    $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge($messageProducerNode->id(), Util::codeIdentifierToNodeId($message->name())));
                }

                return $messageFlow;
            }
        );
    }
}
