<?php
/**
 * This file is part of the prooph/message-flow-analyzer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prooph\MessageFlowAnalyzer;

use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;

final class MessageFlow
{
    /**
     * @var string
     */
    private $project;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Message[]
     */
    private $messages;

    /**
     * @var array
     */
    private $attributes;

    public static function newFlow(string $project, string $rootDir): self
    {
        return new self($project, $rootDir, [
            'messages' => [],
            'attributes' => [],
        ]);
    }

    public static function fromArray(array $flowData): self
    {
        return new self(
            $flowData['project'] ?? '',
            $flowData['rootDir'] ?? '',
            self::flowFromArray($flowData['flow'] ?? [])
        );
    }

    private function __construct(string $project, string $rootDir, array $flow)
    {
        if(mb_strlen($project) === 0) {
            throw new \InvalidArgumentException('Project name must not be empty.');
        }

        if(!is_dir($rootDir)) {
            throw new \InvalidArgumentException('Root dir is not a directory. Got ' . $rootDir);
        }
        $this->project = $project;
        $this->rootDir = $rootDir;
        $this->messages = $flow['messages'] ?? [];
        $this->attributes = $flow['attributes'] ?? [];
    }

    /**
     * @return string
     */
    public function project(): string
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function rootDir(): string
    {
        return $this->rootDir;
    }

    public function knowsMessage(string $messageName): bool
    {
        return array_key_exists($messageName, $this->messages);
    }

    /**
     * @return Message[]
     */
    public function messages(): array
    {
        return $this->messages;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        if(array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    public function setAttribute($name, $value): self
    {
        try {
            MessageDataAssertion::assertPayload(['value' => $value]);
        } catch (\Throwable $error) {
            throw new \InvalidArgumentException("Attribute value should be of type and contain only scalar, NULL or array. Got " . $error->getMessage());
        }

        $cp = clone $this;
        $cp->attributes[$name] = $value;
        return $cp;
    }

    public function addMessage(Message $msg): self
    {
        if($this->knowsMessage($msg->name())) {
            throw new \RuntimeException('Message is already known. Got ' . $msg->name());
        }

        $cp = clone $this;
        $cp->messages[$msg->name()] = $msg;
        return $cp;
    }

    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'rootDir' => $this->rootDir,
            'flow' => $this->flowToArray()
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

    private static function flowFromArray(array $flow): array
    {
        return [
            'messages' => array_map(function ($msg): Message {
                if($msg instanceof Message) {
                    return $msg;
                }

                return Message::fromArray($msg);
            }, $flow['messages'] ?? []),
            'attributes' => $flow['attributes'],
        ];
    }

    private function flowToArray(): array
    {
        return [
            'messages' => array_map(function (Message $msg): array {return $msg->toArray();}, $this->messages),
            'attributes' => $this->attributes,
        ];
    }
}