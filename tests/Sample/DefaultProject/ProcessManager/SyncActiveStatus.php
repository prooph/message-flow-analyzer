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

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\ProcessManager;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Infrastucture\CommandBus;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\DeactivateIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserDeactivated;

class SyncActiveStatus
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function onUserDeactivated(UserDeactivated $event): void
    {
        $this->deactivateIdentities($event->payload()['userId']);
    }

    public function deactivateIdentities(string $userId): void
    {
        //load identities of user
        foreach ($this->loadIdentitesOfUser($userId) as $identityId) {
            $this->commandBus->dispatch(new DeactivateIdentity(['identityId' => $identityId]));
        }
    }

    private function loadIdentitesOfUser(string $userId): array
    {
        return [];
    }
}
