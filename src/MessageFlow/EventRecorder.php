<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

use Prooph\EventSourcing\Aggregate\EventProducerTrait;
use Prooph\EventSourcing\AggregateRoot;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class EventRecorder extends MessageHandlingMethodAbstract
{
    public static function isEventRecorder(ReflectionClass $reflectionClass): bool
    {
        if($reflectionClass->isSubclassOf(AggregateRoot::class)) {
            return true;
        }

        $traits = self::getAllTraits($reflectionClass);

        foreach ($traits as $trait) {
            if($trait->getName() === EventProducerTrait::class) {
                return true;
            }
        }

        return false;
    }

    private static function getAllTraits(ReflectionClass $reflectionClass): array
    {
        $traits = $reflectionClass->getTraits();

        $parentClass = $reflectionClass->getParentClass();

        if($parentClass) {
            $parentTraits = self::getAllTraits($parentClass);
            $traits = array_merge($traits, $parentTraits);
        }

        return $traits;
    }
}