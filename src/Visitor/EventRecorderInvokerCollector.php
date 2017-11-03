<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Visitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class EventRecorderInvokerCollector implements ClassVisitor
{
    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if(!in_array($reflectionClass->getName(), $messageFlow->getKnownCommandHandlers())) {
            return $messageFlow;
        }

        $commandHandler = $this->getCommandHandlerFromMessageFlow($reflectionClass->getName(), $messageFlow);

        $eventRecorders = ScanHelper::findInvokedEventRecorders($commandHandler);

        foreach ($eventRecorders as $eventRecorder) {
            $messageFlow = $messageFlow->setEventRecorderInvoker(
                MessageFlow\EventRecorderInvoker::fromInvokerAndEventRecorder(
                    $commandHandler,
                    $eventRecorder
                )
            );

            $builtEventRecorder = $this->checkIfEventRecorderMethodIsUsedAsFactory($eventRecorder);

            if($builtEventRecorder) {
                $messageFlow = $messageFlow->setEventRecorderInvoker(
                    MessageFlow\EventRecorderInvoker::fromInvokerAndEventRecorder(
                        $eventRecorder,
                        $builtEventRecorder
                    )
                );
            }
        }

        return $messageFlow;
    }

    private function getCommandHandlerFromMessageFlow(string $handler, MessageFlow $messageFlow): MessageFlow\MessageHandler
    {
        foreach ($messageFlow->messages() as $message) {
            foreach ($message->handlers() as $cmdHandler) {
                if($cmdHandler->isClass() && $cmdHandler->class() === $handler) {
                    return $cmdHandler;
                }

                if(!$cmdHandler->isClass() && $cmdHandler->function() === $handler) {
                    return $cmdHandler;
                }
            }
        }

        throw new \RuntimeException("No command handler found for handler identifier: " . $handler);
    }

    private function checkIfEventRecorderMethodIsUsedAsFactory(MessageFlow\EventRecorder $eventRecorder): ?MessageFlow\EventRecorder
    {
        $method = $eventRecorder->toFunctionLike();

        if(!$method->hasReturnType()) {
            return null;
        }

        $returnType = $method->getReturnType();

        if($returnType->isBuiltin()) {
            return null;
        }

        $reflectedReturnType = ReflectionClass::createFromName((string)$returnType);

        if(!MessageFlow\EventRecorder::isEventRecorder($reflectedReturnType)) {
            return null;
        }

        $nodeVisitor = new class($reflectedReturnType) extends NodeVisitorAbstract {
            private $reflectedReturnType;
            private $eventRecorder;
            public function __construct(ReflectionClass $reflectedReturnType)
            {
                $this->reflectedReturnType = $reflectedReturnType;
            }

            public function leaveNode(Node $node)
            {
                if($node instanceof Node\Expr\StaticCall) {
                    if($node->class instanceof Node\Name\FullyQualified
                        && $this->reflectedReturnType->getName() === $node->class->toString()) {
                        $reflectionClass = ReflectionClass::createFromName($node->class->toString());

                        if(!MessageFlow\EventRecorder::isEventRecorder($reflectionClass)) {
                            return;
                        }

                        $reflectionMethod = ReflectionMethod::createFromName($node->class->toString(), $node->name);
                        $this->eventRecorder = MessageFlow\EventRecorder::fromReflectionMethod($reflectionMethod);
                    }
                }
            }

            public function getEventRecorder(): ?MessageFlow\EventRecorder
            {
                return $this->eventRecorder;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        $traverser->traverse($method->getBodyAst());

        return $nodeVisitor->getEventRecorder();
    }
}
