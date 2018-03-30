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

namespace Prooph\MessageFlowAnalyzer\Helper;

use Prooph\MessageFlowAnalyzer\Filter\ExcludeHiddenFileInfo;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeTestsDir;
use Prooph\MessageFlowAnalyzer\Filter\ExcludeVendorDir;
use Prooph\MessageFlowAnalyzer\Filter\IncludePHPFile;
use Prooph\MessageFlowAnalyzer\MessageFlow\Finalizer;
use Prooph\MessageFlowAnalyzer\Output\Formatter;
use Prooph\MessageFlowAnalyzer\Output\JsonPrettyPrint;
use Prooph\MessageFlowAnalyzer\ProjectTraverser;
use Prooph\MessageFlowAnalyzer\Visitor\AggregateMethodCollector;
use Prooph\MessageFlowAnalyzer\Visitor\CommandHandlerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\EventListenerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessageProducerCollector;
use Prooph\MessageFlowAnalyzer\Visitor\MessagingCollector;

final class ProjectTraverserFactory
{
    public static $filterAliases = [
        'ExcludeVendorDir' => ExcludeVendorDir::class,
        'ExcludeTestsDir' => ExcludeTestsDir::class,
        'ExcludeHiddenFileInfo' => ExcludeHiddenFileInfo::class,
        'IncludePHPFile' => IncludePHPFile::class,
    ];

    public static $classVisitorAliases = [
        'MessageCollector' => MessageCollector::class,
        'CommandHandlerCollector' => CommandHandlerCollector::class,
        'MessageProducerCollector' => MessageProducerCollector::class,
        'AggregateMethodCollector' => AggregateMethodCollector::class,
        'EventListenerCollector' => EventListenerCollector::class,
        'MessagingCollector' => MessagingCollector::class,
    ];

    public static $fileInfoVisitorAliases = [];

    public static $finalizerAliases = [];

    public static $outputFormatterAliases = [
        'JsonPrettyPrint' => JsonPrettyPrint::class,
    ];

    public static function buildTraverserFromConfig(array $config): ProjectTraverser
    {
        if (! array_key_exists('name', $config)) {
            throw new \InvalidArgumentException('Missing project name in configuration');
        }

        $traverser = new ProjectTraverser($config['name']);

        foreach ($config['fileInfoFilters'] ?? [] as $filterClass) {
            $filterClass = self::$filterAliases[$filterClass] ?? $filterClass;
            $traverser->addFileInfoFilter(new $filterClass());
        }

        foreach ($config['classVisitors'] ?? [] as $classVisitorClass) {
            $classVisitorClass = self::$classVisitorAliases[$classVisitorClass] ?? $classVisitorClass;
            $traverser->addClassVisitor(new $classVisitorClass());
        }

        foreach ($config['fileInfoVisitors'] ?? [] as $fileInfoVisitorClass) {
            $fileInfoVisitorClass = self::$fileInfoVisitorAliases[$fileInfoVisitorClass] ?? $fileInfoVisitorClass;
            $traverser->addFileInfoVisitor(new $fileInfoVisitorClass());
        }

        return $traverser;
    }

    /**
     * @param array $config
     * @return Finalizer[]
     */
    public static function buildFinalizersFromConfig(array $config): array
    {
        $finalizers = [];

        foreach ($config['finalizers'] ?? [] as $finalizerClass) {
            $finalizerClass = self::$finalizerAliases[$finalizerClass] ?? $finalizerClass;
            $f = new $finalizerClass();
            if (! $f instanceof Finalizer) {
                throw new \InvalidArgumentException("Invalid finalizer: Finalizer interface not implemented by $finalizerClass");
            }
            $finalizers[] = $f;
        }

        return $finalizers;
    }

    public static function buildOutputFormatter(string $nameOrClass): Formatter
    {
        $nameOrClass = self::$outputFormatterAliases[$nameOrClass] ?? $nameOrClass;

        return new $nameOrClass();
    }
}
