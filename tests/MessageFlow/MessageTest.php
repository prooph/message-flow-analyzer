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

namespace ProophTest\MessageFlowAnalyzer\MessageFlow;

use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use ProophTest\MessageFlowAnalyzer\BaseTestCase;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;
use Roave\BetterReflection\Reflection\ReflectionClass;

class MessageTest extends BaseTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_from_reflected_message()
    {
        $reflectedMsg = ReflectionClass::createFromName(RegisterUser::class);

        $message = Message::fromReflectionClass($reflectedMsg);

        $registerUser = new RegisterUser(['bla' => 'blub']);

        $this->assertEquals([
            'name' => $registerUser->messageName(),
            'type' => $registerUser->messageType(),
            'class' => RegisterUser::class,
            'filename' => realpath(__DIR__ . '/../Sample/DefaultProject/Model/User/Command/RegisterUser.php'),
            'handlers' => [],
            'producers' => [],
            'recorders' => [],
        ], $message->toArray());
    }
}
