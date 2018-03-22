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

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\Visitor\AggregateMethodCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use Roave\BetterReflection\Reflection\ReflectionClass;

class AggregateMethodCollectorTest extends BaseTestCase
{
    /**
     * @var AggregateMethodCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new AggregateMethodCollector();
    }

    /**
     * @test
     */
    public function it_detects_recording_of_events()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $user = ReflectionClass::createFromName(User::class);

        $msgFlow = $this->cut->onClassReflection($user, $msgFlow);

        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(User\Event\UserRegistered::class)));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(User\Event\UsernameChanged::class)));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(User::class.'::register')));
        $this->assertTrue($msgFlow->knowsNodeWithId(Util::codeIdentifierToNodeId(User::class.'::changeUsername')));
    }
}
