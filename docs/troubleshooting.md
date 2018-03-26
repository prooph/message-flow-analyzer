# Troubleshooting

## I don't get an output and no result

Did you run the analyze command in very verbose mode?

```php
php vendor/bin/prooph-analyzer project:analyze -vvv
```

If you don't do that and the php process has not enough memory available you don't see anything. In verbose mode
you get an appropriate message.

## Interface or Class not found

In some cases the underlying parser and reflection libs cannot find a class or interface. 
For example in our demo app [proophessor-do](https://github.com/prooph/proophessor-do) we need to exclude classes that implement `Psr\Http\Server\MiddlewareInterface` interface.
For some reason the interface cannot be loaded. We will check the issue but something like that can always happen and
it would be bad if you cannot get a result just because of a weird bug.

Blacklist filters to the rescue! You can exclude problematic files. Here is an example:

```php
<?php

declare(strict_types=1);

namespace Prooph\ProophessorDo\Infrastructure\ProophAnalyzer;

use Prooph\MessageFlowAnalyzer\Filter\FileInfoFilter;

final class ExcludeBlacklistedFiles implements FileInfoFilter
{
    private $blacklist = [
        'src/Container/MongoClientFactory.php',
        'src/Middleware/JsonError.php',
        'src/Middleware/JsonPayload.php',
        'src/App/View/Helper/Url.php',
        'src/Container/Infrastructure/UrlHelperFactory.php',
    ];
    public function accept(\SplFileInfo $fileInfo, string $rootDir): bool
    {
        if($fileInfo->isDir()) {
            return true;
        }
        foreach ($this->blacklist as $entry) {
            if($fileInfo->getPathname() === $rootDir . DIRECTORY_SEPARATOR . $entry) {
                return false;
            }
        }
        return true;
    }
}

``` 
## Missing nodes or edges

You're missing a node or edge? Maybe the default visitors are not able to scan your implementation correctly.
You can use the prooph components in many different ways and we cannot prepare the visitors for every situation.
But you can write your own `visitor`. Just look at the existing implementations. It is actually a lot of fun to write
those visitors.

Another option is to use a finalizer and add missing nodes and/or edges by hand. This is useful in case you want to add
infrastructure as a node and connect it with message flow nodes.

A simple example. You add the event store as a node and add an edge for every found event:

```php
<?php

declare(strict_types=1);

namespace Acme\Custom;

use Prooph\MessageFlowAnalyzer\MessageFlow;
use Prooph\MessageFlowAnalyzer\Helper\Util;

class AddEventStore implements MessageFlow\Finalizer
{
    public function finalize(MessageFlow $messageFlow): MessageFlow
    {
        //Add event store node
        //We use the generic fromArray constructor here
        //another way is to extend the node class and add a Node::asEventStore named constructor
        $esNode = MessageFlow\Node::fromArray([
            'id' => Util::codeIdentifierToNodeId('prooph-event-store'),
            'type' => 'event-store', //we can use custom types!
            'name' => 'prooph Event Store',
            //Cast icon to string, bc fromArray expects the serialized version of an icon
            'icon' => (string)MessageFlow\NodeIcon::faSolid('fa-database'),
            'color' => '#ED6842' //prooph event store orange ;)
        ]);
        
        $messageFlow = $messageFlow->addNode($esNode);
        
        foreach ($messageFlow->nodes() as $node) {
            if($node->type() === MessageFlow\Node::TYPE_EVENT) {
                //Add a new edge with event node id being the source
                //and event store being the target
                $messageFlow = $messageFlow->setEdge(
                    new MessageFlow\Edge(
                        $node->id(),
                        $esNode->id()        
                    )
                );
            }
        }
        
        return $messageFlow;
    }
}
```
