<?php

/**
 * This file is part of prooph/message-flow-analyzer.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

final class EventRecorderInvoker
{
    /**
     * @var string
     */
    private $invokerIdentifier;

    /**
     * @var string
     */
    private $eventRecorderIdentifier;

    public static function fromInvokerAndEventRecorder(MessageHandlingMethodAbstract $invoker, EventRecorder $eventRecorder): self
    {
        return new self(
            $invoker->identifier(),
            $eventRecorder->identifier()
        );
    }

    public static function fromArray(array $data): self
    {
        return new self($data['invokerIdentifier'] ?? '', $data['eventRecorderIdentifier'] ?? '');
    }

    private function __construct(string $invokerIdentifier, string $eventRecorderIdentifier)
    {
        $this->invokerIdentifier = $invokerIdentifier;
        $this->eventRecorderIdentifier = $eventRecorderIdentifier;
    }

    public function identifier(): string
    {
        return $this->invokerIdentifier() . '->' . $this->eventRecorderIdentifier();
    }

    /**
     * @return string
     */
    public function invokerIdentifier(): string
    {
        return $this->invokerIdentifier;
    }

    /**
     * @return string
     */
    public function eventRecorderIdentifier(): string
    {
        return $this->eventRecorderIdentifier;
    }

    public function toArray(): array
    {
        return [
            'invokerIdentifier' => $this->invokerIdentifier,
            'eventRecorderIdentifier' => $this->eventRecorderIdentifier,
        ];
    }

    public function equals($other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->toArray() === $other->toArray();
    }

    public function __toString(): string
    {
        return \json_encode($this->toArray());
    }
}
