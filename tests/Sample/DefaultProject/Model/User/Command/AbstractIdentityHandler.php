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

use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity;
use ProophTest\MessageFlowAnalyzer\Sample\DefaultProject\Model\Identity\IdentityRepository;

class AbstractIdentityHandler
{
    /**
     * @var IdentityRepository
     */
    protected $identityRepository;

    public function __construct(IdentityRepository $repository)
    {
        $this->identityRepository = $repository;
    }

    public function getIdentity(string $identityId): Identity
    {
        return $this->identityRepository->get($identityId);
    }
}
