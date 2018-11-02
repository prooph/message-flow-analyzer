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

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

use Prooph\EventSourcing\Aggregate\EventProducerTrait;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class EventRecorder extends MessageHandlingMethodAbstract
{
    public static function isEventRecorder(ReflectionClass $reflectionClass): bool
    {
        if ($reflectionClass->isSubclassOf(AggregateRoot::class)) {
            return true;
        }

        return Util::usesTrait(EventProducerTrait::class, $reflectionClass);
    }
}
