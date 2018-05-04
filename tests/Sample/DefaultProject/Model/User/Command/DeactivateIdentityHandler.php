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

class DeactivateIdentityHandler extends AbstractIdentityHandler
{
    public function __invoke(DeactivateIdentity $command)
    {
        $identity = $this->getIdentity($command->payload()['identityId']);

        $identity->deactivate();

        $this->identityRepository->save($identity);
    }
}
