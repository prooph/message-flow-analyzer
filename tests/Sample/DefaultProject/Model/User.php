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

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model;

use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserActivated;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserDeactivated;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UsernameChanged;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\UserRegistered;

class User extends AggregateRoot
{
    private $userId;

    public static function register(string $id): self
    {
        $self = new self();
        $self->recordThat(UserRegistered::occur($id, []));

        //Call another event recording method
        $self->activate();

        return $self;
    }

    public function changeUsername(string $username): void
    {
        $usernameChanged = new UsernameChanged($this->userId, $username);
        //Note: this won't work if it would be executed but we want to test that non AggregateChanged events are tracked, too
        $this->recordThat($usernameChanged);
    }

    public function activate(): void
    {
        $this->recordThat(UserActivated::occur($this->userId, []));
    }

    public function deactivate(): void
    {
        $this->recordThat(UserDeactivated::occur($this->userId, []));
    }

    public function addIdentity(string $identityId): Identity
    {
        return Identity::add($identityId);
    }

    protected function aggregateId(): string
    {
        return $this->userId;
    }

    /**
     * Apply given event
     */
    protected function apply(AggregateChanged $event): void
    {
        switch ($event->messageName()) {
            default:
                //do nothing
        }
    }
}
