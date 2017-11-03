<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;

interface IdentityRepository
{
    public function get(string $identityId): Identity;

    public function add(Identity $identity): void;

    public function save(Identity $identity): void;
}