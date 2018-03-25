# CLI Command

The prooph message flow analyzer ships with a CLI command to analyze a project or parts of it.

## Options

Run the following command to get an overview of your options.

```bash
php vendor/bin/prooph-analyzer project:analyze --help
```

## Run With Defaults

```bash
php vendor/bin/prooph-analyzer project:analyze -vvv
```
By default the analyzer uses current working dir as the root of the analysis.
It looks for a config file called `prooph_analyzer.json`. More on this in the configuration section.

A successful run produces a `prooph_message_flow.json` with the results. This file can be imported into
the [prooph Mgmt UI](https://github.com/prooph/event-store-mgmt-ui) message flow app.

*Note: It is recommended to always run the command in very verbose mode **-vvv** to get detailed exception traces in case of an error.*

