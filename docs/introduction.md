# Introduction

A static code analyzer to extract a message flow of a prooph project. Results can be visualized in the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui).

![Model Exploration](https://github.com/prooph/proophessor/blob/master/assets/prooph_do_exploration.gif)

## Installation

```bash
composer require --dev prooph/message-flow-analyzer
```

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

