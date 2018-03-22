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

namespace Prooph\MessageFlowAnalyzer\Output;

use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow;

final class JsonCytoscapeElements implements Formatter
{
    public function messageFlowToString(MessageFlow $messageFlow): string
    {
        $nodes = [];
        $edges = [];
        $eventRecorderClasses = [];

        foreach ($messageFlow->messages() as $message) {
            $msgKey = Util::codeIdentifierToNodeId($message->name());
            $nodes[] = [
                'data' => [
                    'id' => $msgKey,
                    'type' => $message->type(),
                    'name' => Util::withoutNamespace($message->name()),
                    'class' => $message->class(),
                ],
                'classes' => 'message '.$message->type(),
            ];

            foreach ($message->handlers() as $handler) {
                $handlerKey = Util::codeIdentifierToNodeId($handler->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $handlerKey,
                        'name' => $handler->isClass() ? Util::withoutNamespace($handler->class()) : $handler->function(),
                        'class' => $handler->class(),
                        'function' => $handler->function(),
                    ],
                    'classes' => $message->type().' handler',
                ];

                $edges[] = [
                    'data' => [
                        'id' => $msgKey.'_'.$handlerKey,
                        'source' => $msgKey,
                        'target' => $handlerKey,
                    ],
                ];
            }

            foreach ($message->producers() as $producer) {
                $producerKey = Util::codeIdentifierToNodeId($producer->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $producerKey,
                        'name' => $producer->isClass() ? Util::withoutNamespace($producer->class()) : $producer->function(),
                        'class' => $producer->class(),
                        'function' => $producer->function(),
                    ],
                    'classes' => $message->type().' producer',
                ];

                $edges[] = [
                    'data' => [
                        'id' => $producerKey.'_'.$msgKey,
                        'source' => $producerKey,
                        'target' => $msgKey,
                    ],
                ];
            }

            foreach ($message->recorders() as $recorder) {
                $parent = null;
                if ($recorder->isClass()) {
                    $parent = Util::codeIdentifierToNodeId(Util::codeIdentifierWithoutMethod($recorder->identifier()));
                    $nodes[] = [
                        'data' => [
                            'id' => $parent,
                            'name' => Util::withoutNamespace($recorder->class()),
                            'class' => $recorder->class(),
                            'function' => null,
                        ],
                        'classes' => $message->type().' parent',
                    ];
                    $eventRecorderClasses[$recorder->class()] = $recorder;
                }

                $recorderKey = Util::codeIdentifierToNodeId($recorder->identifier());

                $data = [
                    'id' => $recorderKey,
                    'name' => $recorder->isClass() ? Util::withoutNamespace($recorder->class()).'::'.$recorder->function() : $recorder->function(),
                    'class' => $recorder->class(),
                    'function' => $recorder->function(),
                ];

                if ($parent) {
                    $data['parent'] = $parent;
                }

                $nodes[] = [
                    'data' => $data,
                    'classes' => $message->type().' recorder',
                ];

                $edges[] = [
                    'data' => [
                        'id' => $recorderKey.'_'.$msgKey,
                        'source' => $recorderKey,
                        'target' => $msgKey,
                    ],
                ];
            }
        }

        $isEventRecorderClass = function (string $identifier) use ($eventRecorderClasses): bool {
            return array_key_exists(Util::codeIdentifierWithoutMethod($identifier), $eventRecorderClasses);
        };

        $getEventRecorderFactory = function (string $identifer) use ($eventRecorderClasses): MessageFlow\EventRecorder {
            $recorderClass = Util::codeIdentifierWithoutMethod($identifer);
            $factoryMethod = str_replace($recorderClass.MessageFlow\MessageHandlingMethodAbstract::ID_METHOD_DELIMITER, '', $identifer);

            $orgEventRecorder = $eventRecorderClasses[$recorderClass]->toArray();
            $orgEventRecorder['function'] = $factoryMethod;

            return MessageFlow\EventRecorder::fromArray($orgEventRecorder);
        };

        foreach ($messageFlow->eventRecorderInvokers() as $eventRecorderInvoker) {
            $eventRecorderInvokerKey = Util::codeIdentifierToNodeId($eventRecorderInvoker->invokerIdentifier());
            $eventRecorderKey = Util::codeIdentifierToNodeId($eventRecorderInvoker->eventRecorderIdentifier());

            //Special case: EventRecorder method used as factory for another event recorder
            //We want to add following flow to the graph in that case:
            //
            //1.) EventRecorderFactory::method -> BuiltEventRecorder::method -- EventRecorderFactory::method is not available as handler, we need to add it
            //2.)
            if ($isEventRecorderClass($eventRecorderInvoker->invokerIdentifier())) {
                //Add handler for 1.), see above
                $eventRecorderFactory = $getEventRecorderFactory($eventRecorderInvoker->invokerIdentifier());

                $eventRecorderFactoryKey = Util::codeIdentifierToNodeId($eventRecorderFactory->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $eventRecorderFactoryKey,
                        'name' => Util::withoutNamespace($eventRecorderFactory->class()).'::'.$eventRecorderFactory->function(),
                        'class' => $eventRecorderFactory->class(),
                        'function' => $eventRecorderFactory->function(),
                        'parent' => Util::codeIdentifierToNodeId(Util::codeIdentifierWithoutMethod($eventRecorderFactory->identifier())),
                    ],
                    'classes' => 'event factory',
                ];
            }

            $edges[] = [
                'data' => [
                    'id' => $eventRecorderInvokerKey.'_'.$eventRecorderKey,
                    'source' => $eventRecorderInvokerKey,
                    'target' => $eventRecorderKey,
                ],
            ];
        }

        return json_encode([
            'nodes' => $nodes,
            'edges' => $edges,
        ], JSON_PRETTY_PRINT);
    }
}
