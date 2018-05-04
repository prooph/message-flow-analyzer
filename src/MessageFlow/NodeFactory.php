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

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class NodeFactory
{
    /**
     * @var Node
     */
    private static $nodeClass = Node::class;

    public static function useNodeClass(string $nodeClass): void
    {
        $nodeClassRef = ReflectionClass::createFromName($nodeClass);

        if (! $nodeClassRef->isSubclassOf(Node::class) && $nodeClass !== Node::class) {
            throw new \InvalidArgumentException('NodeFactory can only use a sub class of ' . Node::class. ". Got $nodeClass");
        }

        self::$nodeClass = $nodeClass;
    }

    public static function createMessageNode(Message $message): Node
    {
        return self::$nodeClass::asMessage($message);
    }

    public static function createCommandHandlerNode(MessageHandler $handler): Node
    {
        return self::$nodeClass::asCommandHandler($handler);
    }

    public static function createAggregateNode(MessageHandlingMethodAbstract $aggregateMethod): Node
    {
        return self::$nodeClass::asAggregate($aggregateMethod);
    }

    public static function createEventRecordingAggregateMethodNode(EventRecorder $eventRecorder): Node
    {
        return self::$nodeClass::asEventRecordingAggregateMethod($eventRecorder);
    }

    public static function createAggregateFactoryMethodNode(MessageHandlingMethodAbstract $eventRecorderInvoker): Node
    {
        return self::$nodeClass::asAggregateFactoryMethod($eventRecorderInvoker);
    }

    public static function createProcessManagerNode(MessageProducer $messageProducer): Node
    {
        return self::$nodeClass::asProcessManager($messageProducer);
    }

    public static function createMessageProducingServiceNode(MessageProducer $messageProducer, Message $message): Node
    {
        return self::$nodeClass::asMessageProducingService($messageProducer, $message);
    }

    public static function createEventListenerNode(MessageHandler $messageHandler): Node
    {
        return self::$nodeClass::asEventListener($messageHandler);
    }
}
