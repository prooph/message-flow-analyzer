<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Helper;

use Roave\BetterReflection\Reflection\ReflectionClass;

final class Util
{
    public static function usesTrait(string $traitName, ReflectionClass $reflectionClass): bool
    {
        $filteredTrait = array_filter($reflectionClass->getTraits(), function (ReflectionClass $trait) use ($traitName): bool {
            return $trait->getName() === $traitName;
        });

        if(count($filteredTrait)) {
            return true;
        }

        $parentClass = $reflectionClass->getParentClass();

        return $parentClass? self::usesTrait($traitName, $parentClass) : false;
    }
}
