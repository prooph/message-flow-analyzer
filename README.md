# prooph message flow analyzer

[![Build Status](https://travis-ci.org/prooph/message-flow-analyzer.svg?branch=master)](https://travis-ci.org/prooph/message-flow-analyzer)
[![Coverage Status](https://coveralls.io/repos/github/prooph/message-flow-analyzer/badge.svg?branch=master)](https://coveralls.io/github/prooph/message-flow-analyzer?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

A static code analyzer to extract message flow of a prooph project

## Installation

```bash
composer require --dev prooph/message-flow-analyzer
```

## Configuration

The analyzer can be configured using a json file. By default the analyzer uses a `prooph_analyzer.json` located in the current working directory.
An example of a default config can be found in the [test example project](https://github.com/prooph/message-flow-analyzer/blob/master/tests/Sample/DefaultProject/prooph_analyzer.json)

## Run

```bash
php vendor/bin/prooph-analyzer project:analyze
```

## Why?

The prooph message flow analyzer scans your project for prooph messages and collects information how these messages flow through your project source code :)

The analysis contains information about:

- commands, events, queries
- message handlers per message (command handler, event listner, process manager, ...)
- message producers per message (controller, cli commands, process manager, ...)
- event recorders per event (classes implementing prooph's AggregateRoot or using the EventProducerTrait)

The message flow is written to an output file (`prooph_message_flow.json` by default).

For now that's it. But imagine what you can do with this information! We'll add different output formatters to generate config for d3js or draw.io.
The message flow analyzer will also be part of the upcoming `event-store-mgmt-ui` and will allow you to connect the message flow with your event streams
for debugging and monitoring.

## How?

The package uses the excellent libraries [roave/better-reflection](https://github.com/Roave/BetterReflection)
and [nikic/php-parser](https://github.com/nikic/PHP-Parser) (which is used by Roave/BetterReflection internally, too)

## WIP

`prooph/message-flow-analyzer` and the `event-store-mgmt-ui` are work in progress. There is no roadmap defined yet. If you think your project could benefit
from a stable version and you or your company would like to support development then [get in touch](http://getprooph.org/#get-in-touch).

## Filters

You can add include and exclude filters for files and directories. `prooph/message-flow-analyzer` ships with some default filters.
Check the linked example config above. The filter implementations can be found in the [Filter dir](https://github.com/prooph/message-flow-analyzer/tree/master/src/Filter)

## ClassVisitors

Class visitors are called for every php class found in the project and not excluded by a filter.
They take a `Roave\BetterReflection\Reflection\ReflectionClass` and the `Prooph\MessageFlowAnalyzer\MessageFlow` as input and if a visitor finds something
interesting in the class it can add this information to the `MessageFlow`.

Again `prooph/message-flow-analyzer` ships with default class visitors (see example config) which can be found in the [Visitor dir](https://github.com/prooph/message-flow-analyzer/tree/master/src/Visitor). 

## Run it against proophessor-do

You can see `prooph/message-flow-analyzer` in action by running it against [proophessor-do](https://github.com/prooph/proophessor-do).

1. Clone proophessor-do
2. Add `prooph/message-flow-analyzer: dev-master` to the `require-dev` config of proophessor-do's `composer.json`
3. Run composer install
4. Copy [prooph_analyzer.json](https://github.com/prooph/message-flow-analyzer/blob/master/tests/Sample/DefaultProject/prooph_analyzer.json) into root dir of proophessor-do
5. Copy [ExcludeBlacklistedFiles.php](https://gist.github.com/codeliner/6bae2c3a5de0a9f93e1d2143f7196f75#file-excludeblacklistedfiles-php) into `src/Infrastructure/ProophAnalyzer`.
   This is needed because proophessor-do contains a prepared factory for mongodb connection but mongo is not installed by default so the mongo classes cannot be loaded.
6. Add `"Prooph\\ProophessorDo\\Infrastructure\\ProophAnalyzer\\ExcludeBlacklistedFiles"` as last entry in the `prooph_analyzer.json` `fileInfoFilters` array.
7. Run `php vendor/bin/prooph-analyzer project:analyze` and watch the generated output file `prooph_message_flow.json`

If this is too much work right now and you only want to see the result: [prooph_message_flow.json](https://gist.github.com/codeliner/6bae2c3a5de0a9f93e1d2143f7196f75#file-prooph_message_flow-json)

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- File issues at [https://github.com/prooph/message-flow-analyzer/issues](https://github.com/prooph/message-flow-analyzer/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [New BSD License](LICENSE).


