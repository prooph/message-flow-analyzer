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
        ReflectionClass $reflectionClass,
        callable $addMethodToMessageCb,
        MessageFlow $msgFlow): MessageFlow
    {
        foreach ($reflectionClass->getMethods() as $method) {
            $messages = $this->checkMethodProducesMessages($method);
            foreach ($messages as $message) {
                $message = $msgFlow->getMessage($message->name(), $message);
                $message = $addMethodToMessageCb($message, $method);
                $msgFlow = $msgFlow->setMessage($message);
            }
        }

        return $msgFlow;
    }

    /**
     * @param ReflectionMethod $method
     * @return Message[]|null
     */
    private function checkMethodProducesMessages(ReflectionMethod $method): array
    {
        try {
            $bodyAst = $method->getBodyAst();
        } catch (\TypeError $error) {
            return [];
        }

        $this->getTraverser()->traverse($bodyAst);

        return $this->getTraverser()->messageScanner()->popFoundMessages();
    }

    private function getTraverser(): MessageScanningNodeTraverser
    {
        if (null === $this->nodeTraverser) {
            $this->nodeTraverser = new MessageScanningNodeTraverser(new NodeTraverser(), new MessageScanner());
        }

        return $this->nodeTraverser;
    }
}
