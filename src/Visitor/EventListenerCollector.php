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

final class EventListenerCollector implements ClassVisitor
{
    use MessageProducingMethodScanner;

    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getNumberOfParameters() === 1) {
                $messageFlow = $this->inspectMethod($messageFlow, $method);
            }
        }

        return $messageFlow;
    }

    private function inspectMethod(MessageFlow $messageFlow, ReflectionMethod $method): MessageFlow
    {
        $message = ScanHelper::checkIfMethodHandlesMessage($messageFlow, $method);

        if (! $message || $message->type() !== ProophMsg::TYPE_EVENT) {
            return $messageFlow;
        }

        $producedMessages = $this->checkMethodProducesMessages($method);

        if ($producedMessages) {
            //Looks like a process manager or saga, not a simple event listener
            return $messageFlow;
        }

        $handler = MessageFlow\MessageHandler::fromReflectionMethod($method);

        $node = MessageFlow\NodeFactory::createEventListenerNode($handler);

        if (! $messageFlow->knowsNode($node)) {
            $messageFlow = $messageFlow->addNode($node);
        }

        $messageFlow = $messageFlow->setEdge(new MessageFlow\Edge(Util::codeIdentifierToNodeId($message->name()), $node->id()));

        return $messageFlow;
    }
}
