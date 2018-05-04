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

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\Common\Messaging\MessageDataAssertion;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class Message
{
    const MESSAGE_TYPES = [ProophMsg::TYPE_COMMAND, ProophMsg::TYPE_EVENT, ProophMsg::TYPE_QUERY];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $filename;

    public static function isRealMessage(ReflectionClass $proophMessage): bool
    {
        if ($proophMessage->isAnonymous() || $proophMessage->isAbstract() || $proophMessage->isInterface()) {
            return false;
        }

        return true;
    }

    public static function fromReflectionClass(ReflectionClass $proophMessage): self
    {
        if (! $proophMessage->implementsInterface(ProophMsg::class)) {
            throw new \InvalidArgumentException('Reflected message does not implement '.ProophMsg::class.'. Got '.$proophMessage->getName());
        }

        $phpReflectionhMessage = new \ReflectionClass($proophMessage->getName());

        $msgInstance = $phpReflectionhMessage->newInstanceWithoutConstructor();

        $messageName = $proophMessage->getName();

        try {
            if ($phpReflectionhMessage->hasMethod('init')) {
                $init = $phpReflectionhMessage->getMethod('init');
                $init->setAccessible(true);
                $init->invoke($msgInstance);
            }
            $messageName = $phpReflectionhMessage->getMethod('messageName')->invoke($msgInstance);
        } catch (\Throwable $e) {
            $messageName .= '[unknown]';
        }

        $messageType = $phpReflectionhMessage->getMethod('messageType')->invoke($msgInstance);

        return new self(
            $messageName,
            $messageType,
            $phpReflectionhMessage->getName(),
            $phpReflectionhMessage->getFileName()
        );
    }

    public static function fromNode(Node $node): self
    {
        if (! in_array($node->type(), Node::MESSAGE_TYPES)) {
            throw new \InvalidArgumentException('Not a message node: ' . json_encode($node->toArray()));
        }

        return self::fromReflectionClass(ReflectionClass::createFromName($node->class()));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['type'] ?? '',
            $data['class'] ?? '',
            $data['filename'] ?? ''
        );
    }

    private function __construct(string $name, string $type, string $class, string $filename)
    {
        MessageDataAssertion::assertMessageName($name);

        if (! in_array($type, self::MESSAGE_TYPES)) {
            throw new \InvalidArgumentException('Message type must be one of ['.implode(',', self::MESSAGE_TYPES)."]. Got $type");
        }

        if (! class_exists($class)) {
            throw new \InvalidArgumentException("Unknown message class. Got $class");
        }

        if (! file_exists($filename)) {
            throw new \InvalidArgumentException("Message class file not found. Got $filename");
        }

        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'class' => $this->class,
            'filename' => $this->filename,
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
        return json_encode($this->toArray());
    }
}
