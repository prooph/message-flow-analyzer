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

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\MessageFlow\EventRecorder;
use Prooph\MessageFlowAnalyzer\Visitor\EventRecorderCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use Roave\BetterReflection\Reflection\ReflectionClass;

class EventRecorderCollectorTest extends BaseTestCase
{
    /**
     * @var EventRecorderCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new EventRecorderCollector();
    }

    /**
     * @test
     */
    public function it_detects_recording_of_events()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $user = ReflectionClass::createFromName(User::class);

        $msgFlow = $this->cut->onClassReflection($user, $msgFlow);

        $this->assertTrue($msgFlow->knowsMessage(User\Event\UserRegistered::class));
        $this->assertTrue($msgFlow->knowsMessage(User\Event\UsernameChanged::class));

        $userRegistered = $msgFlow->getMessage(User\Event\UserRegistered::class);
        $recorder = $userRegistered->recorders()[User::class.'::register'];
        $this->assertInstanceOf(EventRecorder::class, $recorder);

        $usernameChanged = $msgFlow->getMessage(User\Event\UsernameChanged::class);
        $recorder = $usernameChanged->recorders()[User::class.'::changeUsername'];
        $this->assertInstanceOf(EventRecorder::class, $recorder);
    }
}
