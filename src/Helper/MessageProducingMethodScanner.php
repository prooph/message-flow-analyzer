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

namespace Prooph\MessageFlowAnalyzer\Helper;

use PhpParser\NodeTraverser;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\MessageScanner;
use Prooph\MessageFlowAnalyzer\Helper\PhpParser\MessageScanningNodeTraverser;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

trait MessageProducingMethodScanner
{
    /**
     * @var MessageScanningNodeTraverser
     */
    private $nodeTraverser;

    private function checkMessageProduction(
        MessageFlow $msgFlow,
        ReflectionClass $reflectionClass,
        callable $onMessageProducingMethodCb,
        callable $onNonMessageProducingMethodCb = null,
        MessageClassProvider $messageClassProvider = null,
        array $messageFactoryProperties = []
    ): MessageFlow {
        foreach ($reflectionClass->getMethods() as $method) {
            $messages = $this->checkMethodProducesMessages($method, $messageClassProvider, $messageFactoryProperties);

            if (count($messages)) {
                foreach ($messages as $message) {
                    $message = $msgFlow->getMessage($message->name(), $message);
                    $msgFlow = $onMessageProducingMethodCb($msgFlow, $message, $method);
                }
            } elseif ($onNonMessageProducingMethodCb) {
                $msgFlow = $onNonMessageProducingMethodCb($msgFlow, $method);
            }
        }

        return $msgFlow;
    }

    /**
     * @param ReflectionMethod $method
     * @param MessageClassProvider|null $messageClassProvider
     * @param array $messageFactoryProperties
     * @return array
     */
    private function checkMethodProducesMessages(ReflectionMethod $method, MessageClassProvider $messageClassProvider = null, array $messageFactoryProperties = []): array
    {
        try {
            $bodyAst = $method->getBodyAst();
        } catch (\TypeError $error) {
            return [];
        }

        $traverser = $this->getTraverser($messageClassProvider, $messageFactoryProperties);

        $traverser->traverse($bodyAst);

        return $traverser->messageScanner()->popFoundMessages();
    }

    private function getTraverser(MessageClassProvider $messageClassProvider = null, array $messageFactoryProperties = []): MessageScanningNodeTraverser
    {
        return new MessageScanningNodeTraverser(new NodeTraverser(), new MessageScanner($messageClassProvider, $messageFactoryProperties));
    }
}
