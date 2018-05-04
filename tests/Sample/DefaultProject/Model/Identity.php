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
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\Event\IdentityAdded;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User\Event\IdentityDeactivated;

final class Identity extends EventProducerAbstract
{
    private $identityId;

    public static function add(string $identityId): self
    {
        $self = new self();
        //Note: this won't work if it would be executed but we want to test that non AggregateChanged events are tracked, too
        $self->recordThat(new IdentityAdded($identityId));

        return $self;
    }

    public static function addForUser(string $identityId, string $userId): self
    {
        $self = new self();
        $self->recordThat(new IdentityAdded($identityId, $userId));

        return $self;
    }

    public function deactivate(): void
    {
        $this->recordThat(IdentityDeactivated::occur($this->identityId, []));
    }

    protected function aggregateId(): string
    {
        return $this->identityId;
    }

    /**
     * Apply given event
     */
    protected function apply(AggregateChanged $event): void
    {
    }
}
