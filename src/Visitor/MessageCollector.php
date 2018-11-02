<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

class MessageCollector implements ClassVisitor
{
    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if ($reflectionClass->implementsInterface(ProophMsg::class)) {
            if (! MessageFlow\Message::isRealMessage($reflectionClass)) {
                return $messageFlow;
            }

            $msg = MessageFlow\Message::fromReflectionClass($reflectionClass);
            if (! $messageFlow->knowsMessage($msg->name())) {
                $messageFlow = $messageFlow->addMessage($msg);
            }
        }

        return $messageFlow;
    }
}
