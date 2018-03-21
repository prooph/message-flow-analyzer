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

use Prooph\MessageFlowAnalyzer\MessageFlow\MessageHandlingMethodAbstract;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class Util
{
    public static function usesTrait(string $traitName, ReflectionClass $reflectionClass): bool
    {
        $filteredTrait = array_filter($reflectionClass->getTraits(), function (ReflectionClass $trait) use ($traitName): bool {
            return $trait->getName() === $traitName;
        });

        if (count($filteredTrait)) {
            return true;
        }

        $parentClass = $reflectionClass->getParentClass();

        return $parentClass ? self::usesTrait($traitName, $parentClass) : false;
    }

    public static function withoutNamespace(string $class): string
    {
        $parts = explode('\\', $class);

        return array_pop($parts);
    }

    public static function identifierToKey(string $identifier): string
    {
        return sha1($identifier);
    }

    public static function identifierWithoutMethod(string $identifier): string
    {
        $parts = explode(MessageHandlingMethodAbstract::ID_METHOD_DELIMITER, $identifier);

        return array_shift($parts);
    }
}
