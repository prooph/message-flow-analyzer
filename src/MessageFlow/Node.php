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

use Prooph\MessageFlowAnalyzer\Helper\Util;

class Node
{
    const TYPE_COMMAND = 'command';
    const TYPE_EVENT = 'event';
    const TYPE_QUERY = 'query';
    const TYPE_HANDLER = 'handler';
    const TYPE_AGGREGATE = 'aggregate';
    const TYPE_PROCESS_MANAGER = 'pm';
    const TYPE_SAGA = 'saga';
    const TYPE_PROJECTOR = 'projector';
    const TYPE_FINDER = 'finder';
    const TYPE_LISTENER = 'listener';
    const TYPE_QUEUE = 'queue';
    const TYPE_READ_MODEL = 'readmodel';
    const TYPE_SERVICE = 'service';

    const MESSAGE_TYPES = [
        self::TYPE_COMMAND,
        self::TYPE_EVENT,
        self::TYPE_QUERY,
    ];

    /**
     * Unique identifier of the node
     *
     * @var string
     */
    private $id;

    /**
     * Used as class name in the UI
     *
     * @var string
     */
    private $type;

    /**
     * Name of the node in the UI
     *
     * @var string
     */
    private $name;

    /**
     * File containing the class/function
     *
     * @var string
     */
    private $filename;

    /**
     * Description of the node
     *
     * Becomes a tooltip in the UI
     *
     * @var string|null
     */
    private $description = null;

    /**
     * Class referenced by the node
     *
     * @var string|null
     */
    private $class = null;

    /**
     * Method of the class connect with another node
     *
     * Example:
     * - Command Handler method handling a command (connected node)
     * - Aggregate method called by a command handler method (connected node)
     * - Process manager method listening on event (connected node)
     * - ...
     *
     * @var string/null
     */
    private $method = null;

    /**
     * Global function name (incl. namespace) if node references a function instead of a class
     *
     * @var string/null
     */
    private $funcName = null;

    /**
     * Optional parent node id
     *
     * Nodes with the same parent are grouped in the UI
     *
     * The parent should be a node itself and can be used in an edge
     *
     * Example:
     * Aggregate methods are nodes and their parent is the Aggregate class
     *
     * @var string|null
     */
    private $parent = null;

    /**
     * Additional tags added as class names in the UI
     *
     * @var string[]
     */
    private $tags = [];

    /**
     * FontAwesome icon name for the node
     *
     * If not set a circle is used as default shape
     *
     * @var string/null
     */
    private $icon = null;

    /**
     * If set it overrides the default color used for the node type
     *
     * @var string|null
     */
    private $color = null;

    /**
     * Optional JSON Schema if node references a message or read model.
     *
     * @var array|null
     */
    private $schema = null;

    /**
     * Named constructor for message node
     *
     * @param Message $message
     * @return Node
     */
    public static function asMessage(Message $message): self
    {
        switch ($message->type()) {
            case \Prooph\Common\Messaging\Message::TYPE_COMMAND:
                $color = '#15A2B0';
                break;
            case \Prooph\Common\Messaging\Message::TYPE_EVENT:
                $color = '#ED6842';
                break;
            default:
                $color = '#1F8A6D';
        }

        return (new self(
            Util::codeIdentifierToNodeId($message->name()),
            $message->type(),
            Util::withoutNamespace($message->name()),
            $message->filename(),
            null,
            $message->class()
        ))->withTag('message')->withIcon('fa-envelope')->withColor($color);
    }

    /**
     * Named constructor for command handler nodes
     *
     * Command handler: Function invoked with command that calls an event recorder (aggregate method in most cases)
     *
     * @param MessageHandler $handler
     * @return Node
     */
    public static function asCommandHandler(MessageHandler $handler): self
    {
        if ($handler->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Command handler type {$handler->type()} not supported yet. Found in file {$handler->filename()}");
        }

        $withMethod = true;

        if ($handler->function() === '__invoke' || $handler->function() === 'handle') {
            $withMethod = false;
        }

        $name = ($withMethod) ? Util::withoutNamespace($handler->identifier()) : Util::withoutNamespace($handler->class());

        return (new self(
            Util::codeIdentifierToNodeId($handler->identifier()),
            self::TYPE_HANDLER,
            $name,
            $handler->filename(),
            null,
            $handler->class(),
            $handler->function()
        ))->withTag('command')->withIcon('fa-sign-out-alt')->withColor('#1B1C1D');
    }

    /**
     * Named constructor for aggregate nodes
     *
     * Aggregate nodes are used as parents. They are identified by FQCN and their childs are event recorders
     * aka aggregate methods or functions (in case of prooph/micro)
     *
     * @param MessageHandlingMethodAbstract $aggregateMethod
     * @return Node
     */
    public static function asAggregate(MessageHandlingMethodAbstract $aggregateMethod): self
    {
        if ($aggregateMethod->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Aggregate type {$aggregateMethod->type()} not supported yet. Found in file {$aggregateMethod->filename()}");
        }

        return (new self(
            Util::codeIdentifierToNodeId($aggregateMethod->class()),
            self::TYPE_AGGREGATE,
            Util::withoutNamespace($aggregateMethod->class()),
            $aggregateMethod->filename(),
            null,
            $aggregateMethod->class()
        ))->withTag('parent')->withColor('#e9f2f7');
    }

    /**
     * Named constructor for aggregate method nodes
     *
     * Aggregate methods are event recorders that link to a parent aggregate node, so they are grouped together
     * by aggregate.
     *
     * @param EventRecorder $eventRecorder
     * @return Node
     */
    public static function asEventRecordingAggregateMethod(EventRecorder $eventRecorder): self
    {
        if ($eventRecorder->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Event recorder type {$eventRecorder->type()} not supported yet. Found in file {$eventRecorder->filename()}");
        }

        return (new self(
            Util::codeIdentifierToNodeId($eventRecorder->identifier()),
            self::TYPE_AGGREGATE,
            Util::withoutNamespace($eventRecorder->identifier()),
            $eventRecorder->filename(),
            null,
            $eventRecorder->class(),
            $eventRecorder->function(),
            null,
            Util::codeIdentifierToNodeId($eventRecorder->class())
        ))->withTag('event')->withTag('recorder')->withIcon('fa-shield-check')->withColor('#EECA51');
    }

    /**
     * Named constructor for aggregate methods that act as a factory for another aggregate but do not record events itself.
     *
     * Example:
     * User::postTodo(): Todo
     *
     * @param MessageHandlingMethodAbstract $eventRecorderInvoker
     * @return Node
     */
    public static function asAggregateFactoryMethod(MessageHandlingMethodAbstract $eventRecorderInvoker): self
    {
        if ($eventRecorderInvoker->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Event recorder invoker type {$eventRecorderInvoker->type()} not supported yet. Found in file {$eventRecorderInvoker->filename()}");
        }

        return (new self(
            Util::codeIdentifierToNodeId($eventRecorderInvoker->identifier()),
            self::TYPE_AGGREGATE,
            Util::withoutNamespace($eventRecorderInvoker->identifier()),
            $eventRecorderInvoker->filename(),
            null,
            $eventRecorderInvoker->class(),
            $eventRecorderInvoker->function(),
            null,
            Util::codeIdentifierToNodeId($eventRecorderInvoker->class())
        ))->withTag('event')->withTag('factory')->withIcon('fa-industry')->withColor('#EECA51');
    }

    public static function asEventListener(MessageHandler $messageHandler): self
    {
        if ($messageHandler->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Event listener type {$messageHandler->type()} not supported yet. Found in file {$messageHandler->filename()}");
        }

        $withMethod = true;

        if ($messageHandler->function() === '__invoke' || $messageHandler->function() === 'onEvent') {
            $withMethod = false;
        }

        $name = ($withMethod) ? Util::withoutNamespace($messageHandler->identifier()) : Util::withoutNamespace($messageHandler->class());

        return (new self(
            Util::codeIdentifierToNodeId($messageHandler->identifier()),
            self::TYPE_LISTENER,
            $name,
            $messageHandler->filename(),
            null,
            $messageHandler->class(),
            $messageHandler->function()
        ))->withTag('event')->withIcon('fa-bell')->withColor('#6435C9');
    }

    /**
     * Named constructor for process manager nodes
     *
     * A process manager receives an event and produces a new command
     *
     * @param MessageProducer $messageProducer
     * @return Node
     */
    public static function asProcessManager(MessageProducer $messageProducer): self
    {
        if ($messageProducer->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Process manager type {$messageProducer->type()} not supported yet. Found in file {$messageProducer->filename()}");
        }

        $withMethod = true;

        if ($messageProducer->function() === '__invoke' || $messageProducer->function() === 'onEvent') {
            $withMethod = false;
        }

        $name = ($withMethod) ? Util::withoutNamespace($messageProducer->identifier()) : Util::withoutNamespace($messageProducer->class());

        return (new self(
            Util::codeIdentifierToNodeId($messageProducer->identifier()),
            self::TYPE_PROCESS_MANAGER,
            $name,
            $messageProducer->filename(),
            null,
            $messageProducer->class(),
            $messageProducer->function()
        ))->withTag('command')->withTag('producer')->withIcon('fa-cogs')->withColor('#715671');
    }

    /**
     * Named constructor for message producing service nodes
     *
     * A message producing service can be an MVC controller, a PSR-15 request handler, cli command, application service ...
     *
     * @param MessageProducer $messageProducer
     * @param Message $message
     * @return Node
     */
    public static function asMessageProducingService(MessageProducer $messageProducer, Message $message): self
    {
        if ($messageProducer->type() !== MessageHandlingMethodAbstract::TYPE_CLASS) {
            throw new \InvalidArgumentException("Message producer type {$messageProducer->type()} not supported yet. Found in file {$messageProducer->filename()}");
        }

        return (new self(
            Util::codeIdentifierToNodeId($messageProducer->identifier()),
            self::TYPE_SERVICE,
            $messageProducer->identifier(),
            $messageProducer->filename(),
            null,
            $messageProducer->class(),
            $messageProducer->function()
        ))->withTag($message->type())->withTag('producer')->withIcon('fa-cogs')->withColor('#1B1C1D');
    }

    public static function fromArray(array $nodeData)
    {
        $classes = $nodeData['classes'] ?? null;

        if ($classes) {
            $tags = explode(' ', $classes);
        } else {
            $tags = [];
        }

        return new self(
            $nodeData['data']['id'] ?? '',
            $nodeData['data']['type'] ?? '',
            $nodeData['data']['name'] ?? '',
            $nodeData['data']['filename'] ?? '',
            $nodeData['data']['description'] ?? null,
            $nodeData['data']['class'] ?? null,
            $nodeData['data']['method'] ?? null,
            $nodeData['data']['funcName'] ?? null,
            $nodeData['data']['parent'] ?? null,
            $tags,
            $nodeData['data']['icon'] ?? null,
            $nodeData['data']['color'] ?? null,
            $nodeData['data']['schema'] ?? null
        );
    }

    private function __construct(
        string $id,
        string $type,
        string $name,
        string $filename,
        string $description = null,
        string $class = null,
        string $method = null,
        string $funcName = null,
        string $parent = null,
        array $tags = [],
        string $icon = null,
        string $color = null,
        array $schema = null
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('Node id must not be empty');
        }

        if ($type === '') {
            throw new \InvalidArgumentException('Node type must not be empty');
        }

        if ($name === '') {
            throw new \InvalidArgumentException('Node name must not be empty');
        }

        array_walk($tags, function (string $tag) {
            if ($tag === '') {
                throw new \InvalidArgumentException('A node tag should be a non empty string');
            }
        });

        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
        $this->filename = $filename;
        $this->description = $description;
        $this->class = $class;
        $this->method = $method;
        $this->funcName = $funcName;
        $this->parent = $parent;
        $this->icon = $icon;
        $this->color = $color;
        $this->schema = $schema;

        foreach ($tags as $tag) {
            $this->tags[$tag] = null;
        }
    }

    public function toArray(): array
    {
        return [
            'data' => [
                'id' => $this->id,
                'type' => $this->type,
                'name' => $this->name,
                'filename' => $this->filename,
                'description' => $this->description,
                'class' => $this->class,
                'method' => $this->method,
                'funcName' => $this->funcName,
                'parent' => $this->parent,
                'icon' => $this->icon,
                'color' => $this->color,
                'schema' => $this->schema,
            ],
            'classes' => implode(' ', $this->withTag($this->type)->tags()),
        ];
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
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
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }

    /**
     * @return null|string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return null|string
     */
    public function class()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function funcName(): string
    {
        return $this->funcName;
    }

    /**
     * @return null|string
     */
    public function parent(): ?string
    {
        return $this->parent;
    }

    /**
     * @return string[]
     */
    public function tags(): array
    {
        return array_keys($this->tags);
    }

    /**
     * @return string
     */
    public function icon(): string
    {
        return $this->icon;
    }

    /**
     * @return null|string
     */
    public function color(): ?string
    {
        return $this->color;
    }

    /**
     * @return array|null
     */
    public function schema(): ?array
    {
        return $this->schema;
    }

    public function withName(string $name): self
    {
        $cp = clone $this;
        $cp->name = $name;

        return $cp;
    }

    public function withDescription(string $description): self
    {
        $cp = clone $this;
        $cp->description = $description;

        return $cp;
    }

    public function withoutDescription(): self
    {
        $cp = clone $this;
        $cp->description = null;

        return $cp;
    }

    public function withClass(string $class): self
    {
        $cp = clone $this;
        $cp->class = $class;

        return $cp;
    }

    public function withoutClass(): self
    {
        $cp = clone $this;
        $cp->class = null;

        return $cp;
    }

    public function withMethod(string $method): self
    {
        $cp = clone $this;
        $cp->method = $method;

        return $cp;
    }

    public function withoutMethod(): self
    {
        $cp = clone $this;
        $cp->method = null;

        return $cp;
    }

    public function withFuncName(string $funcName): self
    {
        $cp = clone $this;
        $cp->funcName = $funcName;

        return $cp;
    }

    public function withoutFuncName(): self
    {
        $cp = clone $this;
        $cp->funcName = null;

        return $cp;
    }

    public function withParent(string $parent): self
    {
        $cp = clone $this;
        $cp->parent = $parent;

        return $cp;
    }

    public function withoutParent(): self
    {
        $cp = clone $this;
        $cp->parent = null;

        return $cp;
    }

    public function withTag(string $tag): self
    {
        $cp = clone $this;
        $cp->tags[$tag] = null;

        return $cp;
    }

    public function withoutTag(string $tag): self
    {
        $cp = clone $this;
        if (array_key_exists($tag, $cp->tags())) {
            unset($cp->tags[$tag]);
        }

        return $cp;
    }

    public function withoutTags(): self
    {
        $cp = clone $this;
        $cp->tags = [];

        return $cp;
    }

    public function withIcon(string $icon): self
    {
        $cp = clone $this;
        $cp->icon = $icon;

        return $cp;
    }

    public function withoutIcon(): self
    {
        $cp = clone $this;
        $cp->icon = null;

        return $cp;
    }

    public function withColor(string $color): self
    {
        $cp = clone $this;
        $cp->color = $color;

        return $cp;
    }

    public function withoutColor(): self
    {
        $cp = clone $this;
        $cp->color = null;

        return $cp;
    }

    public function withSchema(array $schema): self
    {
        $cp = clone $this;
        $cp->schema = $schema;

        return $cp;
    }

    public function withoutSchema(): self
    {
        $cp = clone $this;
        $cp->schema = null;

        return $cp;
    }
}
