<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\Filter;

final class ExcludeVendorDir implements FileInfoFilter
{
    public function accept(\SplFileInfo $fileInfo, string $rootDir): bool
    {
        if (! $fileInfo->isDir()) {
            return true;
        }

        if ($fileInfo->getPathname() === $rootDir . DIRECTORY_SEPARATOR . 'vendor') {
            return false;
        }

        return true;
    }
}
