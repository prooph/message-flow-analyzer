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

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

class MessageHandlingMethodAbstract
{
    const TYPE_CLASS = 'class';
    const TYPE_FUNCTION = 'function';
    const TYPE_CLOSURE = 'closure';
    const ID_METHOD_DELIMITER = '::';

    const ALL_TYPES = [self::TYPE_CLASS, self::TYPE_FUNCTION, self::TYPE_CLOSURE];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $function;

    public static function fromReflectionMethod(ReflectionMethod $method): self
    {
        return new static(
            self::TYPE_CLASS,
            $method->getFileName(),
            $method->getName(),
            $method->getDeclaringClass()->getName()
        );
    }

    public static function fromArray(array $data): self
    {
        return new static(
            $data['type'] ?? '',
            $data['filename'] ?? '',
            $data['function'] ?? '',
            $data['class'] ?? null
        );
    }

    private function __construct(string $type, string $filename, string $function, string $class = null)
    {
        if (! in_array($type, self::ALL_TYPES)) {
            throw new \InvalidArgumentException('Message handler should be one of [' . implode(',', self::ALL_TYPES).']. Got ' . $type);
        }
        if (! file_exists($filename)) {
            throw new \InvalidArgumentException('File of message handler could not be found. Got ' . $filename);
        }
        $this->type = $type;
        $this->filename = $filename;
        $this->function = $function;
        $this->class = $class;
    }

    public function identifier(): string
    {
        switch ($this->type) {
            case self::TYPE_CLASS:
                return $this->class() . self::ID_METHOD_DELIMITER . $this->function();
            default:
                return $this->filename() . '\\' . $this->function();

        }
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isClass(): bool
    {
        return $this->type === self::TYPE_CLASS;
    }

    public function class(): string
    {
        return $this->class;
    }

    public function function(): string
    {
        return $this->function;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function toFunctionLike(): ReflectionFunctionAbstract
    {
        if (! $this->isClass()) {
            return ReflectionFunction::createFromName($this->function());
        }

        return ReflectionClass::createFromName($this->class())->getMethod($this->function());
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'filename' => $this->filename,
            'function' => $this->function,
            'class' => $this->class,
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
