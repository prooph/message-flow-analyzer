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

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\IdentityRepository;

class ProophIdentityRepository implements IdentityRepository
{
    public function get(string $identityId): Identity
    {
        // TODO: Implement get() method.
    }

    public function add(Identity $identity): void
    {
        // TODO: Implement add() method.
    }

    public function save(Identity $identity): void
    {
        // TODO: Implement save() method.
    }
}
