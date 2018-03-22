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

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\IdentityRepository;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\UserRepository;

class AddUserIdentityHandler
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var IdentityRepository
     */
    private $identityRepository;

    public function __construct(UserRepository $userRepository, IdentityRepository $identityRepository)
    {
        $this->userRepository = $userRepository;
        $this->identityRepository = $identityRepository;
    }

    public function __invoke(AddUserIdentity $command)
    {
        $user = $this->userRepository->get($command->payload()['userId']);

        $identity = $user->addIdentity($command->payload()['identityId']);

        $this->identityRepository->add($identity);
    }
}
