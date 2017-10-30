<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Filter;

final class IncludePHPFile implements FileInfoFilter
{
    public function accept(\SplFileInfo $fileInfo, string $rootDir): bool
    {
        if(!$fileInfo->isFile()) {
            return true;
        }

        if($fileInfo->getExtension() === "php") {
            return true;
        }

        return false;
    }
}