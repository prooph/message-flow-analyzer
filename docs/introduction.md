# Introduction

A static code analyzer to extract the message flow of your prooph project. The result can be visualized in the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui).

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

The prooph message flow analyzer scans your project and collects information about messages and how they are handled or
produced by your system. The result is a message flow that can be used to visualize and highlight the business logic.
All technical parts of the system are hidden and only the core logic is extracted. This gives you a high level overview of
what the system does and the effects of it.

### Discuss With Domain Experts

You can discuss implementations with your domain experts because all command, event, aggregate names etc. should reflect the
Ubiquitous Language and that's the only information visible in the message flow. If your domain expert cannot read and verify the
flow you should revisit your implementation.

### Living Documentation

The message flow can serve as a living documentation just like you know it from an automatically generated API doc.
The difference here is that no technical information is extracted but instead business knowledge written into code is extracted
and visualized and that's the important information! Only if you get the business logic right your system will have a value for your company.
You can run the analyzer periodically and update the message flow.

### Debugging
Do you know every part of the system? Do you know every single command and event and what action is connected with them?
The message flow will give you a high level overview so that you can find the right place in your code faster. 
Try out the watcher feature of the message flow which is explained in the mgmt UI documentation. You can interact with the application
and the message flow will highlight the parts of the flow that are effected by your current session. This way you can easily see which
processes are involved or triggered.

### New Developers
The message flow visualized in the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui) gives new developers a great overview of the system.
In most cases a new developer has to look at the database schema to get an idea of what is going on. But what can a database tell the developer about behaviour?
It's only purpose is to store state. State is not behaviour. Entity relations are not behaviour! This is only structure but you can't understand a system
by looking at the database structure of it.

With the message flow a new developer gets a better picture and the best thing is: **the picture is NOT static**

### Inter-Process Communication

It is possible to combine the message flow results of different services in the mgmt UI. This means that processes can be tracked
across a service mesh and don't stop at the border of a single bounded context!


## Collected Information

The analysis contains information about:

- commands, events, queries
- message handlers per message (command handler, event listner, process manager, ...)
- message producers per message (controller, cli commands, process manager, ...)
- event recorders per event (classes implementing prooph's AggregateRoot or using the EventProducerTrait)

The message flow is written to an output file (`prooph_message_flow.json` by default).

## How?

The package uses the excellent libraries [roave/better-reflection](https://github.com/Roave/BetterReflection)
and [nikic/php-parser](https://github.com/nikic/PHP-Parser) (which is used by Roave/BetterReflection internally, too)

