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

namespace Prooph\MessageFlowAnalyzer\Helper\PhpParser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Prooph\MessageFlowAnalyzer\MessageFlow\EventRecorder;
use Prooph\MessageFlowAnalyzer\MessageFlow\MessageHandler;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

class ScanHelper
{
    /**
     * @param MessageHandler $messageHandler
     * @return EventRecorder[]
     */
    public static function findInvokedEventRecorders(MessageHandler $messageHandler): array
    {
        if (! $messageHandler->isClass()) {
            return [];
        }

        $reflectedHandler = ReflectionClass::createFromName($messageHandler->class());
        $handleMethod = $reflectedHandler->getMethod($messageHandler->function());

        $repoProperties = self::findEventRecorderRepositoryProperties($reflectedHandler);
        $eventRecorderVariables = self::findEventRecorderVariables($handleMethod, $repoProperties);

        $nodeVisitor = new class($eventRecorderVariables) extends NodeVisitorAbstract {
            /**
             * @var ReflectionClass[]
             */
            private $eventRecorderVariables;
            /**
             * @var EventRecorder[]
             */
            private $eventRecorders = [];

            public function __construct($eventRecorderVariables)
            {
                $this->eventRecorderVariables = $eventRecorderVariables;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Expr\StaticCall) {
                    if ($node->class instanceof Node\Name\FullyQualified) {
                        $reflectionClass = ReflectionClass::createFromName($node->class->toString());

                        if (! EventRecorder::isEventRecorder($reflectionClass)) {
                            return;
                        }

                        $reflectionMethod = ReflectionMethod::createFromName($node->class->toString(), $node->name);
                        $this->eventRecorders[] = EventRecorder::fromReflectionMethod($reflectionMethod);
                    }
                }

                if (! $node instanceof Node\Expr\MethodCall) {
                    return;
                }

                if (! $node->var instanceof Node\Expr\Variable) {
                    return;
                }

                if (! \array_key_exists($node->var->name, $this->eventRecorderVariables)) {
                    return;
                }

                $this->eventRecorders[] = EventRecorder::fromReflectionMethod(
                    $this->eventRecorderVariables[$node->var->name]->getMethod($node->name)
                );
            }

            public function getEventRecorders(): array
            {
                return $this->eventRecorders;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        $traverser->traverse($handleMethod->getBodyAst());

        return $nodeVisitor->getEventRecorders();
    }

    /**
     * Returns array of reflected event recorder classes with keys being the associated property names of the recorder repositories
     *
     * @param ReflectionClass $reflectionClass
     * @return ReflectionClass[] Indexed by eventRecorderRepositoryPropertyName
     */
    public static function findEventRecorderRepositoryProperties(ReflectionClass $reflectionClass): array
    {
        if (! $reflectionClass->hasMethod('__construct')) {
            return [];
        }

        //@TODO Test: parent::__construct but should work, too!
        $constructor = $reflectionClass->getMethod('__construct');

        $properties = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($eventRecorder = self::isEventRecorderRepositoryParameter($parameter)) {
                $propertyName = self::getPropertyNameForParameter($constructor, $parameter->getName());

                if ($propertyName) {
                    $properties[$propertyName] = $eventRecorder;
                }
            }
        }

        return $properties;
    }

    /**
     * Returns array of reflected event recorder classes with keys being the variable names of the recorders used in the method
     *
     * @param ReflectionMethod $method
     * @param array $eventRecorderRepositoryProperties see self::findEventRecorderRepositoryProperties for structure
     * @return ReflectionClass[] indexed by EventRecorder variable name used in the method
     */
    public static function findEventRecorderVariables(ReflectionMethod $method, array $eventRecorderRepositoryProperties): array
    {
        $nodeVisitor = new class($eventRecorderRepositoryProperties) extends NodeVisitorAbstract {
            private $eventRecorderRepositoryProperties;
            private $eventRecorderVariables = [];

            public function __construct(array $eventRecorderRepositoryProperties)
            {
                $this->eventRecorderRepositoryProperties = $eventRecorderRepositoryProperties;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Expr\Assign) {
                    if (! $node->expr instanceof Node\Expr\MethodCall) {
                        return;
                    }

                    if (! $node->expr->var instanceof Node\Expr\PropertyFetch) {
                        return;
                    }

                    /** @var Node\Expr\PropertyFetch $propertyFetch */
                    $propertyFetch = $node->expr->var;

                    if (! $propertyFetch->var instanceof Node\Expr\Variable || $propertyFetch->var->name !== 'this') {
                        return;
                    }

                    if (\array_key_exists($propertyFetch->name, $this->eventRecorderRepositoryProperties)) {
                        $eventRecorder = $this->eventRecorderRepositoryProperties[$propertyFetch->name];
                        $this->eventRecorderVariables[$node->var->name] = $eventRecorder;
                    }
                }
            }

            public function getEventRecorderVariables(): array
            {
                return $this->eventRecorderVariables;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        $traverser->traverse($method->getBodyAst());

        return $nodeVisitor->getEventRecorderVariables();
    }

    private static function isEventRecorderRepositoryParameter(ReflectionParameter $parameter, bool $inspectChildParameters = true): ?ReflectionClass
    {
        if (! $parameter->hasType()) {
            return null;
        }

        if ($parameter->getType()->isBuiltin()) {
            return null;
        }

        $paramReflectionClass = ReflectionClass::createFromName((string) $parameter->getType());

        if (EventRecorder::isEventRecorder($paramReflectionClass)) {
            return $paramReflectionClass;
        }

        //Check if we are dealing with a repository and can find an event recorder in repository methods like add or save
        if ($inspectChildParameters) {
            foreach ($paramReflectionClass->getMethods() as $method) {
                foreach ($method->getParameters() as $parameter) {
                    if ($recorder = self::isEventRecorderRepositoryParameter($parameter, false)) { //Do not scan further childs
                        return $recorder;
                    }
                }
            }
        }

        return null;
    }

    private static function getPropertyNameForParameter(ReflectionMethod $method, string $parameterName): ?string
    {
        $nodeVisitor = new class($parameterName) extends NodeVisitorAbstract {
            private $parameterName;
            private $propertyName;

            public function __construct(string $parameterName)
            {
                $this->parameterName = $parameterName;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Expr\Assign) {
                    if (null !== $this->propertyName) {
                        return;
                    }

                    if (! $node->var instanceof Node\Expr\PropertyFetch) {
                        return;
                    }

                    /** @var Node\Expr\PropertyFetch $propertyFetch */
                    $propertyFetch = $node->var;

                    if (! $propertyFetch->var instanceof Node\Expr\Variable || $propertyFetch->var->name !== 'this') {
                        return;
                    }

                    if (! $node->expr instanceof Node\Expr\Variable) {
                        return;
                    }

                    if ($node->expr->name === $this->parameterName) {
                        $this->propertyName = $propertyFetch->name;
                    }
                }
            }

            public function getPropertyName(): ?string
            {
                return $this->propertyName;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nodeVisitor);
        $traverser->traverse($method->getBodyAst());

        return $nodeVisitor->getPropertyName();
    }
}
