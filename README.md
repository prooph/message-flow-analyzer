# prooph message flow analyzer

[![Build Status](https://travis-ci.org/prooph/message-flow-analyzer.svg?branch=master)](https://travis-ci.org/prooph/message-flow-analyzer)
[![Coverage Status](https://coveralls.io/repos/github/prooph/message-flow-analyzer/badge.svg?branch=master)](https://coveralls.io/github/prooph/message-flow-analyzer?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

A static code analyzer to extract a message flow of a prooph project. Results can be visualized in the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui).

![Model Exploration](https://github.com/prooph/proophessor/blob/master/assets/prooph_do_exploration.gif)

## Installation

```bash
composer require --dev prooph/message-flow-analyzer
```

## Configuration

The analyzer can be configured using a json file. By default the analyzer uses a `prooph_analyzer.json` located in the current working directory.
An example of a default config can be found in the [test example project](https://github.com/prooph/message-flow-analyzer/blob/master/tests/Sample/DefaultProject/prooph_analyzer.json)

## Run

```bash
php vendor/bin/prooph-analyzer project:analyze -vvv
```

## Why?

The prooph message flow analyzer scans your project for prooph messages and collects information how these messages flow through your system :)

The analysis contains information about:

- commands, events, queries
- message handlers per message (command handler, event listner, process manager, ...)
- message producers per message (controller, cli commands, process manager, ...)
- event recorders per event (classes implementing prooph's AggregateRoot or using the EventProducerTrait)

The message flow is written to an output file (`prooph_message_flow.json` by default).

## How?

The package uses the excellent libraries [roave/better-reflection](https://github.com/Roave/BetterReflection)
and [nikic/php-parser](https://github.com/nikic/PHP-Parser) (which is used by Roave/BetterReflection internally, too)


## Filters

You can add include and exclude filters for files and directories. `prooph/message-flow-analyzer` ships with some default filters.
Check the linked example config above. The filter implementations can be found in the [Filter dir](https://github.com/prooph/message-flow-analyzer/tree/master/src/Filter)

## ClassVisitors

Class visitors are called for every php class found in the project and not excluded by a filter.
They take a `Roave\BetterReflection\Reflection\ReflectionClass` and the `Prooph\MessageFlowAnalyzer\MessageFlow` as input and if a visitor finds something
interesting in the class it can add this information to the `MessageFlow`.

Again `prooph/message-flow-analyzer` ships with default class visitors (see example config) which can be found in the [Visitor dir](https://github.com/prooph/message-flow-analyzer/tree/master/src/Visitor). 

## Documentation

Documentation is [in the doc tree](docs/), and can be compiled using [bookdown](http://bookdown.io).

```console
$ php ./vendor/bin/bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

## Run it against proophessor-do

You can see the `prooph/message-flow-analyzer` in action by running it against [proophessor-do](https://github.com/prooph/proophessor-do) or [proophessor-do-symfony](https://github.com/prooph/proophessor-do-symfony).

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/message-flow-analyzer/issues](https://github.com/prooph/message-flow-analyzer/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [New BSD License](LICENSE).


