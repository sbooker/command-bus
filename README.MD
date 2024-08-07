# Command bus
 
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]
[![Build Status](https://travis-ci.org/sbooker/command-bus.svg?branch=0.7.x)](https://travis-ci.org/sbooker/command-bus)
[![codecov](https://codecov.io/gh/sbooker/command-bus/branch/0.7.x/graph/badge.svg?token=3uCI9t0M2Q)](https://codecov.io/gh/sbooker/command-bus)

 And persistent via [Doctrine ORM][doctrine-orm] implementation

## Installation
Install via [Composer][composer]:
```bash
composer require sbooker/command-bus
```
## Nested transactions

Enpoints may use transactions. Strongly recommends use Sbooker/TransactionManager for it.

You need to make sure that firstly do all checks are and throws exceptions.

And only then mutates state of entities.

## Example usage with Symfony and Doctrine (without bundle)

> #### Important!
> Build-in realization persistence layer of command bus require doctrine/dbal:^3.8 and 
> database supports SELECT FOR UPDATE SKIP LOCKED (MySQL 8.0+, PostgreSQL 9.5+)  

Install suggest libraries:
```bash
composer  require sbooker/doctrine-enumerable-type \
          sbooker/doctrine-transaction-handler \
          ramsey/uuid-doctrine \
          doctrine/dbal:^3.8
```

To use default configuration configure as bellow:
```yaml
### config/packages/sbooker_command_bus.yaml
doctrine:
  dbal:
    types:
      command_status: Sbooker\CommandBus\Infrastructure\Persistence\Mapping\StatusType
  orm:
    mappings:
      SbookerCommands:
        type: xml
        prefix: Sbooker\CommandBus
        dir: '%kernel.project_dir%/vendor/sbooker/command-bus/src/Infrastructure/Persistence/Mapping'
        is_bundle: false

services:
  Sbooker\CommandBus\CommandBus:
    class: Sbooker\CommandBus\PersistentCommandBus
    arguments:
      - '@Sbooker\CommandBus\Normalizer'
      - '@Sbooker\TransactionManager\TransactionManager'
      - '@Sbooker\CommandBus\ReadStorage'

  Sbooker\CommandBus\Normalizer:
    class: Sbooker\CommandBus\Normalization\SymfonySerializerNormalizer
    arguments:
      - '@Sbooker\CommandBus\NameGiver'
      - '@serializer'

  Sbooker\CommandBus\Denormalizer:
    class: Sbooker\CommandBus\Normalization\SymfonySerializerDenormalizer
    arguments:
      - '@Sbooker\CommandBus\NameGiver'
      - '@serializer'

  Sbooker\CommandBus\Registry:
    class: Sbooker\CommandBus\Registry\Containerized\ContainerizedRegistry
    arguments:
      - '@command_bus.registry.endpoint_container'
      - '@command_bus.registry.timeout_calculator_container'

  command_bus.registry.endpoint_container:
    class: Sbooker\CommandBus\Registry\Containerized\ContainerAdapter
    arguments:
      - '@service_container'
      - ~
      -
        ## command name to endpoint map

  command_bus.registry.timeout_calculator_container:
    class: Sbooker\CommandBus\Registry\Containerized\ContainerAdapter
    arguments:
      - '@service_container'
      - 'command_bus.timeout_calculator.default'
      -
        ## command name to timeout calculator map

  command_bus.timeout_calculator.default:
    public: true
    class: Sbooker\CommandBus\TimeoutCalculator\Fix
    arguments:
      - 1
      - 3

  command_bus.timeout_calculator.external:
    public: true
    class: Sbooker\CommandBus\TimeoutCalculator\BinExp
    arguments:
      - 10

  Sbooker\CommandBus\NameGiver:
    class: Sbooker\CommandBus\NameGiver\ClassNameMap
    arguments:
      -
        ## FQCN to command name map

  Sbooker\CommandBus\ReadStorage:
    alias: Sbooker\CommandBus\Infrastructure\Persistence\DoctrineRepository

  Sbooker\CommandBus\Infrastructure\Persistence\DoctrineRepository:
    class: Sbooker\CommandBus\Infrastructure\Persistence\DoctrineRepository
    factory: [ '@doctrine', getRepository ]
    arguments:
      - Sbooker\CommandBus\Command
```

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/command-bus.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/command-bus.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/command-bus.svg?style=flat-square

[release]: https://img.shields.io/packagist/v/sbooker/command-bus
[license]: https://github.com/sbooker/command-bus/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/command-bus

[doctrine-orm]: https://www.doctrine-project.org/projects/orm.html
[composer]: https://getcomposer.org
 
