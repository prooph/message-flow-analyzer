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

namespace Prooph\MessageFlowAnalyzer\Helper\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\Helper\MessageClassProvider;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MessageScanner extends NodeVisitorAbstract
{
    private $messages = [];

    /**
     * @var MessageClassProvider
     */
    private $messageClassProvider;

    /**
     * @var ReflectionClass[] indexed by property name
     */
    private $messageFactoryProperties;

    /**
     * MessageScanner constructor.
     * @param MessageClassProvider $messageClassProvider
     * @param ReflectionClass[] $messageFactoryProperties indexed by property name
     */
    public function __construct(MessageClassProvider $messageClassProvider = null, array $messageFactoryProperties = [])
    {
        $this->messageClassProvider = $messageClassProvider;
        $this->messageFactoryProperties = $messageFactoryProperties;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name\FullyQualified) {
                $reflectionClass = ReflectionClass::createFromName($node->class->toString());

                if (! $reflectionClass->implementsInterface(ProophMsg::class)) {
                    return;
                }

                if (! Message::isRealMessage($reflectionClass)) {
                    return;
                }

                $reflectionMethod = ReflectionMethod::createFromName($node->class->toString(), $node->name);

                $returnType = (string) $reflectionMethod->getReturnType();

                if ($returnType === 'self' || $returnType === $node->class->toString()) {
                    $this->messages[] = Message::fromReflectionClass($reflectionClass);
                }
            }
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name\FullyQualified) {
            $reflectionClass = ReflectionClass::createFromName($node->class->toString());

            if ($reflectionClass->implementsInterface(ProophMsg::class)
                && Message::isRealMessage($reflectionClass)) {
                $this->messages[] = Message::fromReflectionClass($reflectionClass);
            }
        }

        if (! $this->messageClassProvider) {
            return;
        }

        if (! $node instanceof Node\Expr\MethodCall) {
            return;
        }

        $messageName = null;

        if (isset($node->var->name) && is_string($node->var->name) && isset($this->messageFactoryProperties[$node->var->name])
            && isset($node->args[0])) {
            $messageNameArg = $node->args[0];

            if ($messageNameArg->value instanceof Node\Expr\ClassConstFetch && $messageNameArg->value->class instanceof Node\Name\FullyQualified) {
                if (mb_strtolower($messageNameArg->value->name) === 'class') {
                    $messageName = (string) $messageNameArg->value->class;
                } else {
                    $messageName = ReflectionClass::createFromName((string) $messageNameArg->value->class)
                        ->getConstant($messageNameArg->value->name);
                }
            }
        }

        if (null === $messageName) {
            return;
        }

        $reflectionClass = ReflectionClass::createFromName($this->messageClassProvider->provideClass($messageName));

        if ($reflectionClass->implementsInterface(ProophMsg::class)
            && Message::isRealMessage($reflectionClass)) {
            $this->messages[] = Message::fromReflectionClass($reflectionClass);
        }
    }

    /**
     * @return Message[]
     */
    public function popFoundMessages(): array
    {
        $messages = $this->messages;
        $this->messages = [];

        return $messages;
    }
}
