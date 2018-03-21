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

namespace Prooph\MessageFlowAnalyzer\Visitor;

use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MessageHandlerCollector implements ClassVisitor
{
    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getNumberOfParameters() === 1 || $method->getNumberOfParameters() === 2) {
                $messageFlow = $this->inspectMethod($method, $messageFlow);
            }
        }

        return $messageFlow;
    }

    private function inspectMethod(ReflectionMethod $method, MessageFlow $messageFlow): MessageFlow
    {
        $parameter = $method->getParameters()[0];

        if (! $parameter->hasType()) {
            return $messageFlow;
        }

        $parameterType = $parameter->getType();

        if ($parameterType->isBuiltin()) {
            return $messageFlow;
        }

        $reflectionClass = ReflectionClass::createFromName((string) $parameterType);

        if (! $reflectionClass->implementsInterface(ProophMsg::class)) {
            return $messageFlow;
        }

        if (! MessageFlow\Message::isRealMessage($reflectionClass)) {
            return $messageFlow;
        }

        $message = MessageFlow\Message::fromReflectionClass($reflectionClass);

        $message = $messageFlow->getMessage($message->name(), $message);

        return $messageFlow->setMessage($message->addHandler(MessageFlow\MessageHandler::fromReflectionMethod($method)));
    }
}
