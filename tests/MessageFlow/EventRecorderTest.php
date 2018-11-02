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

namespace ProophTest\MessageFlowAnalyzer\MessageFlow;

use Prooph\MessageFlowAnalyzer\MessageFlow\EventRecorder;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use Roave\BetterReflection\Reflection\ReflectionClass;

class EventRecorderTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_treats_prooph_aggregate_root_as_event_recorder()
    {
        $user = ReflectionClass::createFromName(User::class);

        $this->assertTrue(EventRecorder::isEventRecorder($user));
    }

    /**
     * @test
     */
    public function it_detects_usage_of_event_producer_trait()
    {
        $identity = ReflectionClass::createFromName(Identity::class);

        $this->assertTrue(EventRecorder::isEventRecorder($identity));
    }
}
