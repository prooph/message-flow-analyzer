<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer;

use PHPUnit\Framework\TestCase;
use Prooph\MessageFlowAnalyzer\MessageFlow;

class BaseTestCase extends TestCase
{
    protected function getDefaultProjectMessageFlow(): MessageFlow
    {
        return MessageFlow::newFlow('default', __DIR__ . '/Sample/DefaultProject');
    }
}