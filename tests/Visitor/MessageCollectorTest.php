<?php

/**
 * This file is part of the prooph/message-flow-analyzer.
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

namespace ProophTest\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\Visitor\MessageCollector;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageCollectorTest extends BaseTestCase
{
    /**
     * @var MessageCollector
     */
    private $cut;

    protected function setUp()
    {
        $this->cut = new MessageCollector();
    }

    /**
     * @test
     */
    public function it_adds_message_to_message_flow()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $registerUserRef = ReflectionClass::createFromName(RegisterUser::class);

        $this->assertFalse($msgFlow->knowsMessage(RegisterUser::class));

        $msgFlow = $this->cut->onClassReflection($registerUserRef, $msgFlow);

        $this->assertTrue($msgFlow->knowsMessage(RegisterUser::class));
    }

    /**
     * @test
     */
    public function it_does_nothing_if_reflection_class_is_not_a_prooph_message()
    {
        $msgFlow = $this->getDefaultProjectMessageFlow();

        $msgFlow2 = $this->cut->onClassReflection(ReflectionClass::createFromName(BaseTestCase::class), $msgFlow);

        $this->assertTrue($msgFlow->equals($msgFlow2));
    }
}
