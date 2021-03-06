<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\UserRepository;

class RegisterUserHandler
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $repository)
    {
        $this->userRepository = $repository;
    }

    public function __invoke(RegisterUser $command)
    {
        $user = User::register($command->payload()['userId']);

        $this->userRepository->add($user);
    }
}
