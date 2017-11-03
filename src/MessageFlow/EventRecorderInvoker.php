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

final class EventRecorderInvoker
{
    /**
     * @var string
     */
    private $messageHandlerIdentifier;

    /**
     * @var string
     */
    private $eventRecorderIdentifier;

    public static function fromMessageHandlerAndEventRecorder(MessageHandler $messageHandler, EventRecorder $eventRecorder): self
    {
        return new self(
            $messageHandler->identifier(),
            $eventRecorder->identifier()
        );
    }

    public static function fromArray(array $data): self
    {
        return new self($data['messageHandlerIdentifier'] ?? '', $data['eventRecorderIdentifier'] ?? '');
    }

    private function __construct(string $messageHandlerIdentifier, string $eventRecorderIdentifier)
    {
        $this->messageHandlerIdentifier = $messageHandlerIdentifier;
        $this->eventRecorderIdentifier = $eventRecorderIdentifier;
    }

    public function identifier(): string
    {
        return $this->messageHandlerIdentifier() . '->' . $this->eventRecorderIdentifier();
    }

    /**
     * @return string
     */
    public function messageHandlerIdentifier(): string
    {
        return $this->messageHandlerIdentifier;
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
            'messageHandlerIdentifier' => $this->messageHandlerIdentifier,
            'eventRecorderIdentifier' => $this->eventRecorderIdentifier
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
