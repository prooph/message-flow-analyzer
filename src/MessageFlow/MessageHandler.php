<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

final class MessageHandler
{
    /**
     * @var string
     */
    private $class;

    public static function fromArray(array $data): self
    {
        return new self();
    }

    private function __construct()
    {
        /* Map data to private props */
    }

    public function class(): string
    {
        return $this->class;
    }

    public function toArray(): array
    {
        return [

        ];
    }

    public function equals($other): bool
    {
        if(!$other instanceof self) {
            return false;
        }

        return $this->toArray() === $other->toArray();
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

}