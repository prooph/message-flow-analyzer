<?php
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

final class JsonCytoscapeElements implements Formatter
{
    public function messageFlowToString(MessageFlow $messageFlow): string
    {
        $nodes = [];
        $edges = [];
        $eventRecorderClasses = [];

        $cmdProducerXPos = 0;
        $cmdXPos = 200;
        $cmdHandlerXPos = 500;
        $eventRecorderXPos = 750;
        $eventXPos = 1000;
        $eventHandlerXPos = 1250;
        $yStep = 200;

        $currentYpos = 0;
        $cmdYPos = [];
        $cmdHandlerYPos = [];
        $eventRecorderYPos = [];
        $eventYPos = [];

        foreach ($messageFlow->messages() as $message) {
            if($message->type() === 'command') {
                $cmdYPos[Util::identifierToKey($message->name())] = $currentYpos;
                foreach ($message->handlers() as $handler) {
                    $handlerKey = Util::identifierToKey($handler->identifier());
                    $cmdHandlerYPos[$handlerKey] = $currentYpos;
                }
                $currentYpos += $yStep;
            }
        }

        foreach ($messageFlow->eventRecorderInvokers() as $eventRecorderInvoker) {
            $eventRecorderInvokerKey = Util::identifierToKey($eventRecorderInvoker->invokerIdentifier());
            $eventRecorderKey = Util::identifierToKey($eventRecorderInvoker->eventRecorderIdentifier());

            if(array_key_exists($eventRecorderInvokerKey, $cmdHandlerYPos)) {
                $eventRecorderYPos[$eventRecorderKey] = $cmdHandlerYPos[$eventRecorderInvokerKey];
            }
        }

        foreach ($messageFlow->messages() as $message) {
            if($message->type() === 'event') {
                foreach ($message->recorders() as $eventRecorder) {
                    $eventRecorderKey = Util::identifierToKey($eventRecorder->identifier());
                    if(array_key_exists($eventRecorderKey, $eventRecorderYPos)) {
                        $eventYPos[Util::identifierToKey($message->name())] = $eventRecorderYPos[$eventRecorderKey];
                    }
                }
            }
        }


        foreach ($messageFlow->messages() as $message) {
            $msgKey = Util::identifierToKey($message->name());
            $nodes[] = [
                'data' => [
                    'id' => $msgKey,
                    'type' => $message->type(),
                    'name' => Util::withoutNamespace($message->name()),
                    'class' => $message->class()
                ],
                'classes' => "message ".$message->type(),
                'position' => [
                    'x' => $message->type() === 'command'? $cmdXPos : $eventXPos,
                    'y' => $message->type() === 'command'? ($cmdYPos[$msgKey] ?? 0) : ($eventYPos[$msgKey] ?? 0),
                ]

            ];

            foreach ($message->handlers() as $handler) {
                $handlerKey = Util::identifierToKey($handler->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $handlerKey,
                        'name' => $handler->isClass()? Util::withoutNamespace($handler->class()) : $handler->function(),
                        'class' => $handler->class(),
                        'function' => $handler->function(),
                    ],
                    'classes' => $message->type().' handler',
                    'position' => [
                        'x' => $message->type() === 'command'? $cmdHandlerXPos : $eventHandlerXPos,
                        'y' => $message->type() === 'command'? ($cmdHandlerYPos[$handlerKey] ?? 0) : ($eventYPos[$msgKey] ?? 0),
                    ]
                ];

                $edges[] = [
                    'data' => [
                        'id' => $msgKey.'_'.$handlerKey,
                        'source' => $msgKey,
                        'target' => $handlerKey
                    ],
                ];
            }

            foreach ($message->producers() as $producer) {
                $producerKey = Util::identifierToKey($producer->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $producerKey,
                        'name' => $producer->isClass()? Util::withoutNamespace($producer->class()) : $producer->function(),
                        'class' => $producer->class(),
                        'function' => $producer->function(),
                    ],
                    'classes' => $message->type().' producer',
                    'position' => [
                        'x' => $cmdProducerXPos,
                        'y' => $message->type() === 'command'? ($cmdYPos[$msgKey] ?? 0) : ($eventYPos[$msgKey] ?? 0),
                    ]
                ];

                $edges[] = [
                    'data' => [
                        'id' => $producerKey.'_'.$msgKey,
                        'source' => $producerKey,
                        'target' => $msgKey,
                    ]
                ];
            }

            foreach ($message->recorders() as $recorder) {
                $parent = null;
                if($recorder->isClass()) {
                    $parent = Util::identifierToKey(Util::identifierWithoutMethod($recorder->identifier()));
                    $nodes[] = [
                        'data' => [
                            'id' => $parent,
                            'name' => Util::withoutNamespace($recorder->class()),
                            'class' => $recorder->class(),
                            'function' => null
                        ],
                        'classes' => $message->type().' parent',
                    ];
                    $eventRecorderClasses[$recorder->class()] = $recorder;
                }

                $recorderKey = Util::identifierToKey($recorder->identifier());

                $data = [
                    'id' => $recorderKey,
                    'name' => $recorder->isClass()? Util::withoutNamespace($recorder->class()).'::'.$recorder->function() : $recorder->function(),
                    'class' => $recorder->class(),
                    'function' => $recorder->function(),
                ];

                if($parent) {
                    $data['parent'] = $parent;
                }

                $nodes[] = [
                    'data' => $data,
                    'classes' => $message->type().' recorder',
                    'position' => [
                        'x' => $eventRecorderXPos,
                        'y' => $eventYPos[$msgKey] ?? 0,
                    ],
                ];

                $edges[] = [
                    'data' => [
                        'id' => $recorderKey.'_'.$msgKey,
                        'source' => $recorderKey,
                        'target' => $msgKey,
                    ]
                ];
            }
        }

        $isEventRecorderClass = function (string $identifier) use ($eventRecorderClasses): bool
        {
            return array_key_exists(Util::identifierWithoutMethod($identifier), $eventRecorderClasses);
        };

        $getEventRecorderFactory = function (string $identifer) use ($eventRecorderClasses): MessageFlow\EventRecorder {
            $recorderClass = Util::identifierWithoutMethod($identifer);
            $factoryMethod = str_replace($recorderClass.MessageFlow\MessageHandlingMethodAbstract::ID_METHOD_DELIMITER, '', $identifer);

            $orgEventRecorder = $eventRecorderClasses[$recorderClass]->toArray();
            $orgEventRecorder['function'] = $factoryMethod;
            return MessageFlow\EventRecorder::fromArray($orgEventRecorder);
        };

        foreach ($messageFlow->eventRecorderInvokers() as $eventRecorderInvoker) {
            $eventRecorderInvokerKey = Util::identifierToKey($eventRecorderInvoker->invokerIdentifier());
            $eventRecorderKey = Util::identifierToKey($eventRecorderInvoker->eventRecorderIdentifier());

            //Special case: EventRecorder method used as factory for another event recorder
            //We want to add following flow to the graph in that case:
            //
            //1.) EventRecorderFactory::method -> BuiltEventRecorder::method -- EventRecorderFactory::method is not available as handler, we need to add it
            //2.)
            if($isEventRecorderClass($eventRecorderInvoker->invokerIdentifier())) {
                //Add handler for 1.), see above
                $eventRecorderFactory = $getEventRecorderFactory($eventRecorderInvoker->invokerIdentifier());

                $eventRecorderFactoryKey = Util::identifierToKey($eventRecorderFactory->identifier());
                $nodes[] = [
                    'data' => [
                        'id' => $eventRecorderFactoryKey,
                        'name' => Util::withoutNamespace($eventRecorderFactory->class()).'::'.$eventRecorderFactory->function(),
                        'class' => $eventRecorderFactory->class(),
                        'function' => $eventRecorderFactory->function(),
                        'parent' => Util::identifierToKey(Util::identifierWithoutMethod($eventRecorderFactory->identifier())),
                    ],
                    'classes' => 'event factory',
                    'position' => [
                        'x' => $eventRecorderXPos,
                        'y' => $eventRecorderYPos[$eventRecorderKey] ?? 0,
                    ]
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
            'edges' => $edges
        ], JSON_PRETTY_PRINT);
    }
}
