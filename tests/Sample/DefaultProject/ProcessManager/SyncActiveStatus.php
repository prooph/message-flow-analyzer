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

use Prooph\Common\Messaging\MessageFactory;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Infrastucture\CommandBus;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\ActivateUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\DeactivateIdentity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Command\DeactivateUser;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\IdentityActivated;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\IdentityDeactivated;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserDeactivated;

class SyncActiveStatus
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    public function __construct(CommandBus $commandBus, MessageFactory $messageFactory)
    {
        $this->commandBus = $commandBus;
        $this->messageFactory = $messageFactory;
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

    public function onIdentityDeactivated(IdentityDeactivated $event): void
    {
        foreach ($this->loadIdentitesOfUser($event->payload()['userId']) as $identityId) {
            //... check if all identities are deactivated ...
            $deactivateUser = $this->messageFactory->createMessageFromArray(DeactivateUser::class, []);
            $this->commandBus->dispatch($deactivateUser);
        }
    }

    public function onIdentityActivated(IdentityActivated $event): void
    {
        //... check if user needs to be activated ...
        $this->commandBus->dispatch(
            $this->messageFactory->createMessageFromArray(
                ActivateUser::NAME,
                ['userId' => $event->payload()['userId']]
            )
        );
    }

    private function loadIdentitesOfUser(string $userId): array
    {
        return [];
    }
}
