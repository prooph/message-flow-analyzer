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

namespace Prooph\MessageFlowAnalyzer\Filter;

final class ExcludeHiddenFileInfo implements FileInfoFilter
{
    public function accept(\SplFileInfo $fileInfo, string $rootDir): bool
    {
        $filename = $fileInfo->getFilename();
        // Skip hidden files and directories.
        if ($filename[0] === '.') {
            return false;
        }

        return true;
    }
}
