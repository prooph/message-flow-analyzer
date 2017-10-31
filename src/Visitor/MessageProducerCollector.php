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
use Prooph\EventSourcing\Aggregate\EventProducerTrait;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\PhpParser\MessageScanner;
use Prooph\MessageFlowAnalyzer\PhpParser\MessageScanningNodeTraverser;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class MessageProducerCollector implements ClassVisitor
{
    /**
     * @var MessageScanningNodeTraverser
     */
    private $nodeTraverser;

    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow
    {
        if($reflectionClass->implementsInterface(ProophMsg::class)) {
            return $messageFlow;
        }

        if($this->isAggregateRoot($reflectionClass)) {
            return $messageFlow;
        }

        return $this->checkMessageProduction($reflectionClass, $messageFlow);
    }

    private function isAggregateRoot(ReflectionClass $reflectionClass): bool
    {
        if($reflectionClass->isSubclassOf(AggregateRoot::class)) {
            return true;
        }

        $traits = $reflectionClass->getTraits();

        foreach ($traits as $trait) {
            if($trait->getName() === EventProducerTrait::class) {
                return true;
            }
        }

        return false;
    }

    private function checkMessageProduction(ReflectionClass $reflectionClass, MessageFlow $msgFlow): MessageFlow
    {
        foreach ($reflectionClass->getMethods() as $method) {
            $messages = $this->checkMethodProducesMessages($method);
            foreach ($messages as $message) {
                $message = $msgFlow->getMessage($message->name(), $message);
                $message = $message->addProducer(MessageFlow\MessageProducer::fromReflectionMethod($method));
                $msgFlow = $msgFlow->setMessage($message);
            }
        }

        return $msgFlow;
    }

    /**
     * @param ReflectionMethod $method
     * @return MessageFlow\Message[]|null
     */
    private function checkMethodProducesMessages(ReflectionMethod $method): array
    {
        $this->getTraverser()->traverse($method->getBodyAst());
        return $this->getTraverser()->messageScanner()->popFoundMessages();
    }

    private function getTraverser(): MessageScanningNodeTraverser
    {
        if(null === $this->nodeTraverser) {
            $this->nodeTraverser = new MessageScanningNodeTraverser(new NodeTraverser(), new MessageScanner());
        }

        return $this->nodeTraverser;
    }
}