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

namespace ProophTest\MessageFlowAnalyzer;

use PHPUnit\Framework\TestCase;
use Prooph\MessageFlowAnalyzer\Helper\MessageClassProvider;
use Prooph\MessageFlowAnalyzer\MessageFlow;
use Prooph\MessageFlowAnalyzer\Visitor\MessagingCollector;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ActivateUser;
use Prophecy\Argument;

class BaseTestCase extends TestCase
{
    protected function getDefaultProjectMessageFlow(): MessageFlow
    {
        $messageClassProvider = $this->prophesize(MessageClassProvider::class);
        $messageClassProvider->provideClass(Argument::exact('ActivateUser'))->willReturn(ActivateUser::class);
        $messageClassProvider->provideClass(Argument::not(Argument::exact('ActivateUser')))->will(function ($args) {
            return $args[0];
        });

        MessagingCollector::useMessageClassProvider($messageClassProvider->reveal());

        return MessageFlow::newFlow('default', __DIR__ . '/Sample/DefaultProject');
    }
}
