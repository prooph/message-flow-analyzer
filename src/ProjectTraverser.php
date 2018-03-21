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

namespace Prooph\MessageFlowAnalyzer;

use Prooph\MessageFlowAnalyzer\Filter\FileInfoFilter;
use Prooph\MessageFlowAnalyzer\Visitor\ClassVisitor;
use Prooph\MessageFlowAnalyzer\Visitor\FileInfoVisitor;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

final class ProjectTraverser
{
    /**
     * @var string
     */
    private $project;

    /**
     * @var ClassVisitor[]
     */
    private $classVisitors = [];

    /**
     * @var FileInfoVisitor[]
     */
    private $fileInfoVisitors = [];

    /**
     * @var FileInfoFilter
     */
    private $fileInfoFilters = [];

    public function __construct(string $project, array $fileInfoFilters = [], array $classVisitors = [], array $fileInfoVisitors = [])
    {
        //Use PHPs runtime type validation
        array_walk($fileInfoFilters, function (FileInfoFilter $filter) {
        });
        array_walk($classVisitors, function (ClassVisitor $visitor) {
        });
        array_walk($fileInfoVisitors, function (FileInfoVisitor $visitor) {
        });

        $this->project = $project;
        $this->classVisitors = $classVisitors;
        $this->fileInfoFilters = $fileInfoFilters;
    }

    public function addFileInfoFilter(FileInfoFilter $filter): void
    {
        $this->fileInfoFilters[] = $filter;
    }

    public function addClassVisitor(ClassVisitor $classVisitor): void
    {
        $this->classVisitors[] = $classVisitor;
    }

    public function addFileInfoVisitor(FileInfoVisitor $fileInfoVisitor): void
    {
        $this->fileInfoVisitors[] = $fileInfoVisitor;
    }

    public function traverse(string $dir): MessageFlow
    {
        $msgFlow = MessageFlow::newFlow($this->project, $dir);

        $directory = new \RecursiveDirectoryIterator($msgFlow->rootDir());
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current) use ($msgFlow) {
            foreach ($this->fileInfoFilters as $filter) {
                if (! $filter->accept($current, $msgFlow->rootDir())) {
                    return false;
                }
            }

            return true;
        });
        $iterator = new \RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /** @var $file \SplFileInfo */
            if ($file->isFile()) {
                $msgFlow = $this->handleFile($file, $msgFlow);
            }
        }

        return $msgFlow;
    }

    private function handleFile(\SplFileInfo $fileInfo, MessageFlow $msgFlow): MessageFlow
    {
        foreach ($this->fileInfoVisitors as $visitor) {
            $msgFlow = $visitor->onFileInfo($fileInfo, $msgFlow);
        }

        if (! $fileInfo->getExtension() === 'php') {
            return $msgFlow;
        }

        //@TODO Check if PHP file contains functions and add a FunctionVisitor

        $astLocator = (new BetterReflection())->astLocator();
        $fileLocator = new SingleFileSourceLocator($fileInfo->getPathname(), $astLocator);
        $sourceReflector = new ClassReflector($fileLocator);
        $classes = $sourceReflector->getAllClasses();

        foreach ($classes as $class) {
            //Recreate reflection class so that autoloader is used under the hood (not the case with file source loader)
            $class = ReflectionClass::createFromName($class->getName());
            foreach ($this->classVisitors as $visitor) {
                $msgFlow = $visitor->onClassReflection($class, $msgFlow);
            }
        }

        return $msgFlow;
    }
}
