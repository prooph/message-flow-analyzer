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

use PhpParser\NodeTraverser;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;

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
                MessageFlow\EventRecorderInvoker::fromMessageHandlerAndEventRecorder(
                    $commandHandler,
                    $eventRecorder
                )
            );
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
}
