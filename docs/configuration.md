# Configuration

The analyzer can be configured using a json file. By default the analyzer uses a `prooph_analyzer.json` located in the current working directory.
An example of a default config can be found in the [test example project](https://github.com/prooph/message-flow-analyzer/blob/master/tests/Sample/DefaultProject/prooph_analyzer.json)

## fileInfoFilters

You can add include and exclude filters for files and directories.

Configuration should be a List of filter classes implementing `Prooph\MessageFlowAnalyzer\Filter\FileInfoFilter`.

```json
{
  "fileInfoFilters": [
    "ExcludeVendorDir",
    "ExcludeTestsDir",
    "ExcludeHiddenFileInfo",
    "IncludePHPFile",
    "Acme\\Custom\\Filter" 
  ]
}
```
A filter should be constructable without arguments. The message flow analyzer ships with a set of default
filters listed above. The last filter in the example is a project specific filter. Such filters needed to be listed
with their full qualified class name. Default filters are aliased (namespace is excluded).

The filter interface defines a simple method:

```php
<?php

interface FileInfoFilter
{
    public function accept(\SplFileInfo $fileInfo, string $rootDir): bool;
}
```
Return `true` to include current file or directory or `false` otherwise.

## fileInfoVisitors

A file info visitor is called for every included file. It can inspect the file and add information to the
message flow.

Configuration: 

```json
{
  "fileInfoVisitors": [
    "Acme\\Custom\\FileInfoVistor1", 
    "Acme\\Custom\\FileInfoVistor2" 
  ]
}
```
A file info visitor should be constructable without arguments.
Not default visitors are available but you can write your own.
The interface is:

```php
<?php

namespace Prooph\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\MessageFlow;

interface FileInfoVisitor
{
    public function onFileInfo(\SplFileInfo $info, MessageFlow $messageFlow): MessageFlow;
}
```
## classVisitors

A special form of file info visitors are so called `classVisitors`. Class visitors are only invoked if 
a file contains one or more PHP classes and each class visitor is invoked for each class found.
This means that `FileInfoVisitor`s are always invoked but `ClassVisitor`s only for classes.

Configuration: 

```json
{
  "classVisitors": [
    "MessageCollector",
    "CommandHandlerCollector",
    "MessageProducerCollector",
    "AggregateMethodCollector",
    "EventListenerCollector",
    "Acme\\Custom\\ClassVisitor"
  ]
}
```
Again, all visitors except the last one are built-in. The custom visitor needs to be configured using its FQCN.

Dedicated function visitors will be added in a future version of the analyzer. For now you need to implement a 
`FileInfoVisitor` if you want to analyze global functions.

A class visitor should be constructable without arguments.
The interface is:

```php
<?php

declare(strict_types=1);

namespace Prooph\MessageFlowAnalyzer\Visitor;

use Prooph\MessageFlowAnalyzer\MessageFlow;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface ClassVisitor
{
    public function onClassReflection(ReflectionClass $reflectionClass, MessageFlow $messageFlow): MessageFlow;
}
```

## Finalizers

Finalizers are invoked after project scan is completed. They get the final message flow injected and can add not analyzed
nodes, missing edges or modify the existing ones. This can be very handy if you want to complete the message flow with 
information that cannot be analyzed during project scan (for example used databases or queues).

Configuration:

```json
{
  "finalizers": [
    "Acme\\Custom\\Finalizer1", 
    "Acme\\Custom\\Finalizer2" 
  ]
}
```
No default finalizers are available but you can add your own by implementing:

```php
<?php

declare(strict_types=1);

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

use Prooph\MessageFlowAnalyzer\MessageFlow;

interface Finalizer
{
    public function finalize(MessageFlow $messageFlow): MessageFlow;
}
```
Let's take the example from above again. This time we want to change the icon of all message nodes with a finalizer.
So the analyzer still uses the default `MessageCollector` but we change the icon after a scan.

```php
<?php

declare(strict_types=1);

namespace Acme\Custom;

use Prooph\MessageFlowAnalyzer\MessageFlow;

class PaperPlaneMessageIcon implements MessageFlow\Finalizer
{
    public function finalize(MessageFlow $messageFlow): MessageFlow
    {
        foreach ($messageFlow->nodes() as $node) {
            if($this->isMessageNode($node)) {
                $messageFlow = $messageFlow->setNode(
                    $node->withIcon(MessageFlow\NodeIcon::faSolid('fa-paper-plane'))
                );
            }
        }
        
        return $messageFlow;
    }
    
    private function isMessageNode(MessageFlow\Node $node): bool 
    {
        return array_key_exists($node->type(), MessageFlow\Node::MESSAGE_TYPES);
    }
}
```

## Custom Node Class

You can tell the message flow analyzer to use a custom `Node` class instead of the default one.

Just extend your own node class from `Prooph\MessageFlowAnalyzer\MessageFlow\Node` and point to it in the configuration:

```json
{
  "nodeClass": "Acme\\Custom\\Node"
}
```

Again the message icon example. This time we override the named constructor for messages of the node class with the same result:
All message nodes will use the `fa-paper-plane` icon instead of the default `fa-envelope` set by the default node class.

```php
<?php

declare(strict_types=1);

namespace Acme\Custom;

use Prooph\MessageFlowAnalyzer\MessageFlow;
use Prooph\MessageFlowAnalyzer\MessageFlow\Node as DefaultNode;

class Node extends DefaultNode
{
    public static function asMessage(MessageFlow\Message $message): DefaultNode
    {
        $msgNode = parent::asMessage($message);
        
        return $msgNode->withIcon(MessageFlow\NodeIcon::faSolid('fa-paper-plane'));
    }
}
```

## Output formatter

An output formatter can be passed as an option to the CLI command. 

```bash
php vendor/bin/prooph-analyzer project:analyze -vvv -f Acme\\Custom\\Formatter
```
A custom formatter should implement:

```php
<?php

declare(strict_types=1);

namespace Prooph\MessageFlowAnalyzer\Output;

use Prooph\MessageFlowAnalyzer\MessageFlow;

interface Formatter
{
    public function messageFlowToString(MessageFlow $messageFlow): string;
}
```
Its task is to serialize the message flow. The default output formatter is fairly simple:

```php
<?php

declare(strict_types=1);

namespace Prooph\MessageFlowAnalyzer\Output;

use Prooph\MessageFlowAnalyzer\MessageFlow;

final class JsonPrettyPrint implements Formatter
{
    public function messageFlowToString(MessageFlow $messageFlow): string
    {
        return json_encode($messageFlow->toArray(), JSON_PRETTY_PRINT);
    }
}
```
The resulting json string is read by the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui) to draw
the nodes and edges of the message flow.

`JSON_PRETTY_PRINT` is used by default to get a human readable file. If you want send the file over the wire you might use
your own output formatter without the pretty print option or maybe you want to import the nodes and edges in a graph database
and need a different format. 

## Custom Icons

Checkout the `Prooph\MessageFlowAnalyzer\MessageFlow\NodeIcon` class:

```php
<?php

namespace Prooph\MessageFlowAnalyzer\MessageFlow;

final class NodeIcon
{
    public const FA_SOLID = 'fas';
    public const FA_REGULAR = 'far';
    public const FA_BRAND = 'fab';
    public const LINK = 'link';

    public const TYPES = [
        self::FA_SOLID,
        self::FA_REGULAR,
        self::FA_BRAND,
        self::LINK,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $icon;

    public static function faSolid(string $icon): self
    {
        return new self(self::FA_SOLID, $icon);
    }

    public static function faRegular(string  $icon): self
    {
        return new self(self::FA_REGULAR, $icon);
    }

    public static function faBrand(string $icon): self
    {
        return new self(self::FA_BRAND, $icon);
    }

    public static function link(string $link): self
    {
        return new self(self::LINK, $link);
    }

    public static function fromString(string $icon): self
    {
        [$type, $icon] = explode(' ', $icon);

        return new self($type, $icon);
    }

    /* ... */
}
```
You can use all **free** [font awesome icons](https://fontawesome.com/icons?d=gallery) for nodes or set a link to a custom icon instead.

```php
$node = $node->withIcon(MessageFlow\NodeIcon::faSolid('fa-paper-plane'));
$node = $node->withIcon(MessageFlow\NodeIcon::faBrand('fa-php'));
$node = $node->withIcon(MessageFlow\NodeIcon::faRegular('fa-bell'));
$node = $node->withIcon(MessageFlow\NodeIcon::link('https://static.acme.com/assets/logo.svg'));
```









