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

namespace ProophTest\MessageFlowAnalyzer\Helper\PhpParser;

use Prooph\MessageFlowAnalyzer\Helper\PhpParser\ScanHelper;
use Prooph\MessageFlowAnalyzer\MessageFlow\MessageHandler;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUserHandler;
use Roave\BetterReflection\Reflection\ReflectionClass;

class ScanHelperTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_finds_event_recorder_repository_property()
    {
        $reflectionClass = ReflectionClass::createFromName(RegisterUserHandler::class);

        $properties = ScanHelper::findEventRecorderRepositoryProperties($reflectionClass);

        $this->assertArrayHasKey('userRepository', $properties);

        $this->assertEquals(User::class, $properties['userRepository']->getName());
    }

    /**
     * @test
     */
    public function it_finds_event_recorder_variables_in_command_handler_method()
    {
        $reflectionClass = ReflectionClass::createFromName(User\Command\ChangeUsernameHandler::class);

        $properties = ScanHelper::findEventRecorderRepositoryProperties($reflectionClass);

        $variables = ScanHelper::findEventRecorderVariables(
            $reflectionClass->getMethod('handle'),
            $properties
        );

        $this->assertArrayHasKey('user', $variables);
        $this->assertEquals(User::class, $variables['user']->getName());
    }

    /**
     * @test
     */
    public function it_finds_invoked_event_recorders()
    {
        $messageHandler = MessageHandler::fromReflectionMethod(
            ReflectionClass::createFromName(User\Command\ChangeUsernameHandler::class)->getMethod('handle')
        );

        $eventRecorders = ScanHelper::findInvokedEventRecorders($messageHandler);

        $this->assertCount(1, $eventRecorders);
        $this->assertEquals(User::class.'::changeUsername', $eventRecorders[0]->identifier());
    }
}
