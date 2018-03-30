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

namespace Prooph\MessageFlowAnalyzer;

use Prooph\Common\Messaging\Message as ProophMsg;
use Prooph\MessageFlowAnalyzer\Helper\Util;
use Prooph\MessageFlowAnalyzer\MessageFlow\Edge;
use Prooph\MessageFlowAnalyzer\MessageFlow\Message;
use Prooph\MessageFlowAnalyzer\MessageFlow\Node;
use Prooph\MessageFlowAnalyzer\MessageFlow\NodeFactory;

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
     * @var Node[]
     */
    private $nodes = [];

    /**
     * @var Edge[]
     */
    private $edges = [];

    /**
     * Internal command handler cache
     *
     * Is reset when a new command is added
     *
     * @var array
     */
    private $commandHandlers;

    public static function newFlow(string $project, string $rootDir): self
    {
        return new self($project, $rootDir);
    }

    public static function fromArray(array $flowData): self
    {
        return new self(
            $flowData['project'] ?? '',
            $flowData['rootDir'] ?? '',
            $flowData['nodes'] ?? [],
            $flowData['edges'] ?? []
        );
    }

    private function __construct(string $project, string $rootDir, array $nodes = [], array $edges = [])
    {
        if (mb_strlen($project) === 0) {
            throw new \InvalidArgumentException('Project name must not be empty.');
        }

        if (! is_dir($rootDir)) {
            throw new \InvalidArgumentException('Root dir is not a directory. Got ' . $rootDir);
        }
        $this->project = $project;
        $this->rootDir = $rootDir;
        $this->nodes = $nodes;
        $this->edges = $edges;
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

    /**
     * @return Node[] indexed by node id
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Edge[] indexed by edge id
     */
    public function edges(): array
    {
        return $this->edges;
    }

    public function knowsMessage(Message $message): bool
    {
        return array_key_exists(Util::codeIdentifierToNodeId($message->name()), $this->nodes);
    }

    public function getMessage(string $name, Message $default = null): ?Message
    {
        $msgId = Util::codeIdentifierToNodeId($name);
        if (! array_key_exists($msgId, $this->nodes)) {
            return $default;
        }

        return Message::fromNode($this->nodes[$msgId]);
    }

    /**
     * @return Message[]
     */
    public function messages(): array
    {
        return array_map(function (Node $node): Message {
            return Message::fromNode($node);
        }, array_filter($this->nodes, function (Node $node) {
            return in_array($node->type(), Node::MESSAGE_TYPES);
        }));
    }

    public function addMessage(Message $msg): self
    {
        if ($this->knowsMessage($msg)) {
            throw new \RuntimeException('Message is already known. Got ' . $msg->name());
        }

        return $this->setMessage($msg);
    }

    public function setMessage(Message $msg): self
    {
        $cp = clone $this;
        $node = NodeFactory::createMessageNode($msg);
        $cp->nodes[$node->id()] = $node;

        if ($msg->type() === ProophMsg::TYPE_COMMAND) {
            $cp->commandHandlers = null;
        }

        return $cp;
    }

    public function knowsNode(Node $node): bool
    {
        return $this->knowsNodeWithId($node->id());
    }

    public function knowsNodeWithId(string $nodeId): bool
    {
        return array_key_exists($nodeId, $this->nodes);
    }

    public function addNode(Node $node): self
    {
        if ($this->knowsNode($node)) {
            throw new \RuntimeException("Node with id {$node->id()} is already set. Got " . json_encode($node->toArray()));
        }

        return $this->setNode($node);
    }

    public function setNode(Node $node): self
    {
        $cp = clone $this;
        $cp->nodes[$node->id()] = $node;

        return $cp;
    }

    public function getNode(string $nodeId, Node $default = null): ?Node
    {
        if (! $this->knowsNodeWithId($nodeId)) {
            return $default;
        }

        return $this->nodes[$nodeId];
    }

    public function removeNode(string $nodeId): self
    {
        if (! $this->knowsNodeWithId($nodeId)) {
            return $this;
        }

        $cp = clone $this;
        unset($cp[$nodeId]);

        foreach ($this->edges as $edge) {
            if ($edge->sourceNodeId() === $nodeId || $edge->targetNodeId() === $nodeId) {
                $cp = $cp->removeEdge($edge);
            }
        }

        return $cp;
    }

    public function setEdge(Edge $edge): self
    {
        $cp = clone $this;
        $cp->edges[$edge->id()] = $edge;

        return $cp;
    }

    public function removeEdge(Edge $edge): self
    {
        if (! isset($this->edges[$edge->id()])) {
            return $this;
        }

        $cp = clone $this;

        unset($cp[$edge->id()]);

        return $cp;
    }

    /**
     * Returns a list of class and/or function names of command handlers
     *
     * @return string[]
     */
    public function getKnownCommandHandlers(): array
    {
        if (null === $this->commandHandlers) {
            $this->commandHandlers = [];

            foreach ($this->nodes as $node) {
                if ($node->type() === Node::TYPE_HANDLER) {
                    $this->commandHandlers[] = $node->class() ? $node->class() : $node->funcName();
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
            'nodes' => array_values(array_map(function (Node $node): array {
                return $node->toArray();
            }, $this->nodes)),
            'edges' => array_values(array_map(function (Edge $edge): array {
                return $edge->toArray();
            }, $this->edges)),
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
