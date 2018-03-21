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

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Controller;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Infrastucture\CommandBus;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ChangeUsername;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\RegisterUser;

class UserController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function postAction(array $data): void
    {
        $this->commandBus->dispatch(RegisterUser::registerWithUserId($data['userId']));
    }

    public function patchAction(array $data): void
    {
        $this->commandBus->dispatch(ChangeUsername::withNewName($data['name']));
    }
}
