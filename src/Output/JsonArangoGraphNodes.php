<?php

/**
 * This file is part of the prooph/message-flow-analyzer.
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

namespace Prooph\MessageFlowAnalyzer\Output;

use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow;

final class JsonArangoGraphNodes implements Formatter
{
    public function messageFlowToString(MessageFlow $messageFlow): string
    {
        $messages = [];
        $handlers = [];
        $edges = [];
        $eventRecorderClasses = [];

        foreach ($messageFlow->messages() as $message) {
            $msgKey = Util::identifierToKey($message->name());
            $messages[$msgKey] = [
                '_key' => $msgKey,
                'type' => $message->type(),
                'name' => Util::withoutNamespace($message->name()),
                'class' => $message->class(),
            ];

            foreach ($message->handlers() as $handler) {
                $handlerKey = Util::identifierToKey($handler->identifier());
                $handlers[$handlerKey] = [
                    '_key' => $handlerKey,
                    'name' => $handler->isClass() ? Util::withoutNamespace($handler->class()) : $handler->function(),
                    'class' => $handler->class(),
                    'function' => $handler->function(),
                ];

                $edges[] = [
                    '_from' => 'messages/'.$msgKey,
                    '_to' => 'handlers/'.$handlerKey,
                ];
            }

            foreach ($message->producers() as $producer) {
                $producerKey = Util::identifierToKey($producer->identifier());
                $handlers[$producerKey] = [
                    '_key' => $producerKey,
                    'name' => $producer->isClass() ? Util::withoutNamespace($producer->class()) : $producer->function(),
                    'class' => $producer->class(),
                    'function' => $producer->function(),
                ];

                $edges[] = [
                    '_from' => 'handlers/'.$producerKey,
                    '_to' => 'messages/'.$msgKey,
                ];
            }

            foreach ($message->recorders() as $recorder) {
                if ($recorder->isClass()) {
                    $eventRecorderClasses[$recorder->class()] = $recorder;
                }

                $recorderKey = Util::identifierToKey($recorder->identifier());
                $handlers[$recorderKey] = [
                    '_key' => $recorderKey,
                    'name' => $recorder->isClass() ? Util::withoutNamespace($recorder->class()).'::'.$recorder->function() : $recorder->function(),
                    'class' => $recorder->class(),
                    'function' => $recorder->function(),
                ];

                if ($recorder->isClass()) {
                    $recorderClassKey = Util::identifierToKey(Util::identifierWithoutMethod($recorder->identifier()));

                    $handlers[$recorderClassKey] = [
                        '_key' => $recorderClassKey,
                        'name' => Util::withoutNamespace($recorder->class()),
                        'class' => $recorder->class(),
                        'function' => null,
                    ];

                    $edges[] = [
                        '_from' => 'handlers/'.$recorderClassKey,
                        '_to' => 'handlers/'.$recorderKey,
                    ];
                    $edges[] = [
                        '_from' => 'handlers/'.$recorderKey,
                        '_to' => 'messages/'.$msgKey,
                    ];
                } else {
                    $edges[] = [
                        '_from' => 'handlers/'.$recorderKey,
                        '_to' => 'messages/'.$msgKey,
                    ];
                }
            }
        }

        $isEventRecorderClass = function (string $identifier) use ($eventRecorderClasses): bool {
            return \array_key_exists(Util::identifierWithoutMethod($identifier), $eventRecorderClasses);
        };

        $getEventRecorderFactory = function (string $identifer) use ($eventRecorderClasses): MessageFlow\EventRecorder {
            $recorderClass = Util::identifierWithoutMethod($identifer);
            $factoryMethod = \str_replace($recorderClass.MessageFlow\MessageHandlingMethodAbstract::ID_METHOD_DELIMITER, '', $identifer);

            $orgEventRecorder = $eventRecorderClasses[$recorderClass]->toArray();
            $orgEventRecorder['function'] = $factoryMethod;

            return MessageFlow\EventRecorder::fromArray($orgEventRecorder);
        };

        foreach ($messageFlow->eventRecorderInvokers() as $eventRecorderInvoker) {
            //Special case: EventRecorder method used as factory for another event recorder
            //We want to add following flow to the graph in that case:
            //
            //1.) EventRecorderFactory -> EventRecorderFactory::method -- EventRecorderFactory::method is not available as handler, we need to add it
            //2.) EventRecorderFactory::method -> BuiltEventRecorder -- This edge needs to be added to and is the definition stored in $messageFlow
            //3.) BuiltEventRecorder -> BuiltEventRecorder::method -- already added as an edge as event recorder
            if ($isEventRecorderClass($eventRecorderInvoker->invokerIdentifier())) {
                //Add handler for 1.), see above
                $eventRecorderFactory = $getEventRecorderFactory($eventRecorderInvoker->invokerIdentifier());

                $handlers[Util::identifierToKey($eventRecorderFactory->identifier())] = [
                    '_key' => Util::identifierToKey($eventRecorderFactory->identifier()),
                    'name' => Util::withoutNamespace($eventRecorderFactory->class()).'::'.$eventRecorderFactory->function(),
                    'class' => $eventRecorderFactory->class(),
                    'function' => $eventRecorderFactory->function(),
                ];

                //Add 1. edge for factory case (see above)
                $edges[] = [
                    '_from' => 'handlers/'.Util::identifierToKey(Util::identifierWithoutMethod($eventRecorderInvoker->invokerIdentifier())),
                    '_to' => 'handlers/'.Util::identifierToKey($eventRecorderInvoker->invokerIdentifier()),
                ];
            }

            //Add 2. egde for factory case (see above), or normal message handler invokes event recorder case
            $edges[] = [
                '_from' => 'handlers/'.Util::identifierToKey($eventRecorderInvoker->invokerIdentifier()),
                '_to' => 'handlers/'.Util::identifierToKey(Util::identifierWithoutMethod($eventRecorderInvoker->eventRecorderIdentifier())),
            ];
        }

        return \json_encode([
            'messages' => \array_values($messages),
            'handlers' => \array_values($handlers),
            'edges' => $edges,
        ], JSON_PRETTY_PRINT);
    }
}
