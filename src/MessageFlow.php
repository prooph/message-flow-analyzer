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
use Prooph\MessageFlowAnalyzer\MessageFlow\EventRecorderInvoker;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\Common\Messaging\Message as ProophMsg;

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
     * @var EventRecorderInvoker[]
     */
    private $eventRecorderInvokers;

    /**
     * @var array
     */
    private $attributes;

    /**
     * Internal cache which is reset when a new command message is set
     *
     * @var string[]
     */
    private $commandHandlers;

    public static function newFlow(string $project, string $rootDir): self
    {
        return new self($project, $rootDir, [
            'messages' => [],
            'eventRecorderInvokers' => [],
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
        $this->eventRecorderInvokers = $flow['eventRecorderInvokers'] ?? [];
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

    public function getMessage(string $name, Message $efault = null): ?Message
    {
        if(!array_key_exists($name, $this->messages())) {
            return $efault;
        }

        return $this->messages[$name];
    }

    /**
     * @return Message[]
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return EventRecorderInvoker[]
     */
    public function eventRecorderInvokers(): array
    {
        return $this->eventRecorderInvokers;
    }

    public function setEventRecorderInvoker(EventRecorderInvoker $invoker): self
    {
        $cp = clone $this;
        $cp->eventRecorderInvokers[$invoker->identifier()] = $invoker;
        return $cp;
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

        return $this->setMessage($msg);
    }

    public function setMessage(Message $msg): self
    {
        $cp = clone $this;
        $cp->messages[$msg->name()] = $msg;

        //Reset internal cmd handler cache
        if($msg->type() === ProophMsg::TYPE_COMMAND) {
            $cp->commandHandlers = null;
        }

        return $cp;
    }

    /**
     * Returns a list of class and/or function names of command handlers
     *
     * @return string[]
     */
    public function getKnownCommandHandlers(): array
    {
        if(null === $this->commandHandlers) {
            $this->commandHandlers = [];

            foreach ($this->messages() as $message) {
                if($message->type() !== ProophMsg::TYPE_COMMAND) {
                    continue;
                }

                foreach ($message->handlers() as $handler) {
                    $this->commandHandlers[] = $handler->isClass()? $handler->class() : $handler->function();
                }
            }
        }

        return $this->commandHandlers;
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
            'eventRecorderInvokers' => array_map(function ($invoker): EventRecorderInvoker {
                if($invoker instanceof EventRecorderInvoker) {
                    return $invoker;
                }

                return EventRecorderInvoker::fromArray($invoker);
            }, $flow['eventRecorderInvoker'] ?? []),
            'attributes' => $flow['attributes'],
        ];
    }

    private function flowToArray(): array
    {
        return [
            'messages' => array_map(function (Message $msg): array {return $msg->toArray();}, $this->messages),
            'eventRecorderInvokers' => array_map(function (EventRecorderInvoker $invoker): array {return $invoker->toArray();}, $this->eventRecorderInvokers),
            'attributes' => $this->attributes,
        ];
    }
}
