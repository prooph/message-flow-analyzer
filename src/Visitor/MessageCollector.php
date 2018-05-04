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
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

class MessageCollector implements ClassVisitor
{
    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        try {
            if ($reflectionClass->implementsInterface(ProophMsg::class)) {
                if (! MessageFlow\Message::isRealMessage($reflectionClass)) {
                    return $messageFlow;
                }

                $msg = MessageFlow\Message::fromReflectionClass($reflectionClass);
                if (! $messageFlow->knowsMessage($msg)) {
                    $messageFlow = $messageFlow->addMessage($msg);
                }
            }
        } catch (IdentifierNotFound $exception) {
            //An Interface cannot be found, this error can be ignored
        }

        return $messageFlow;
    }
}
