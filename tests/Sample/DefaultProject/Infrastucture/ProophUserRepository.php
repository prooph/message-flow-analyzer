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

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Infrastucture;

use Prooph\EventSourcing\Aggregate\AggregateRepository;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\User;

class ProophUserRepository extends AggregateRepository implements User\UserRepository
{
    public function get(string $userId): User
    {
        return $this->getAggregateRoot($userId);
    }

    public function add(User $user): void
    {
        $this->saveAggregateRoot($user);
    }

    public function save(User $user): void
    {
        $this->saveAggregateRoot($user);
    }
}
