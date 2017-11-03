<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\UserRepository;

class ChangeUsernameHandler
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function handle(ChangeUsername $command): void
    {
        $user = $this->userRepository->get($command->payload()['userId']);

        $user->changeUsername($command->payload()['username']);

        $this->userRepository->save($user);
    }
}
