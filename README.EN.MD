[Read in Russian](README.MD)

# Asynchronous Command Bus (`sbooker/command-bus`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

A reliable, transactional, persistent bus for asynchronous command execution with retry support and state tracking.

## Library's Purpose

This library solves two key challenges in modern applications:

**1. Reliable Asynchronous Task Execution.**
The library allows you to **atomically save a command** for a long-running or unreliable operation (sending an email, making an API request) to the database. A separate background process (worker) will then reliably execute this command later.

**2. Providing a Foundation for Fault-Tolerant Process Managers (Sagas).**
In event-driven systems, domain event processing can be fragile. For example, if a subscriber to an `OrderPaid` event tries to execute a `Warehouse::reserve()` operation and fails, the entire event processing queue can get blocked.
The solution is to make event handling simple and error-free by converting an event or a sequence of events into one or more commands. This is usually done using the **Process Manager (Saga)** pattern.
**The responsibility for converting an event into a command lies with your code** (within an event subscriber).
This library **does not implement** the Process Manager pattern itself, but it **significantly facilitates its creation** by providing a **reliable, transactional mechanism for the asynchronous execution of that command**, isolating failures and preventing other events from being blocked.

## Key Features

*   **Foundation for Process Managers / Sagas:** Provides a reliable command execution mechanism, which is a key part of a Process Manager implementation.
*   **Transactional Reliability:** Accepting a command is an atomic operation thanks to integration with [sbooker/transaction-manager](https://github.com/sbooker/transaction-manager).
*   **Declarative Retry Strategies:** The retry logic is not in the handler. It is **declaratively configured for each command** via a `TimeoutCalculator`. The persistent `Command` entity itself tracks the number of attempts and the time of the next run. The worker merely tries to execute commands that are ready to run.
*   **At-Least-Once Delivery:** A command will be executed until it succeeds or runs out of attempts.
*   **State Tracking:** You can check the status of a command (`created`, `pending`, `success`, `fail`) at any time.
*   **Result Hooks:** You can define callbacks (`Invokers`) to be executed on the successful or failed completion of a command.

## Installation

```bash
composer require sbooker/command-bus
```
You will also need dependencies from the ecosystem and adapters:
```bash
composer require sbooker/transaction-manager sbooker/workflow sbooker/event-loop-worker
composer require sbooker/doctrine-transaction-handler # If you use Doctrine
```

## Quick Start

### Scenario 1: Asynchronous Task Execution

**Goal:** Send a welcome email without making the user wait.

**1. The Command and its Endpoint:**
```php
// src/Mailing/Command/SendEmail.php
final class SendEmail { /* ... DTO with to, subject, body ... */ }

// src/Mailing/Endpoint/SendEmailEndpoint.php
use Sbooker\CommandBus\Endpoint;
final class SendEmailEndpoint implements Endpoint
{
    private Mailer $mailer;
    public function process(UuidInterface $id, object $payload): void
    {
        /** @var SendEmail $payload */
        $this.mailer->send(/* ... */);
    }
}
```

**2. Dispatching the Command:**
```php
// src/UseCase/RegisterUser/Handler.php
/** @var Sbooker\CommandBus\CommandBus $commandBus */

$command = new SendEmail('welcome@user.com', 'Welcome!', '...');
$commandBus->accept(Uuid::uuid4(), $command); // Fast and reliable
```

### Scenario 2 (Advanced): Building a Process Manager

**Goal:** Reliably reserve items in a warehouse after an order has been paid (`OrderPaid` event).

**1. The Command and its Endpoint:**
```php
// src/Warehouse/Command/ReserveItems.php
final class ReserveItems { /* ... DTO with orderId, items ... */ }

// src/Warehouse/Endpoint/ReserveItemsEndpoint.php
use Sbooker\CommandBus\Endpoint;
final class ReserveItemsEndpoint implements Endpoint
{
    private WarehouseService $warehouseService;
    public function process(UuidInterface $id, object $payload): void
    {
        /** @var ReserveItems $payload */
        $this.warehouseService->reserve(/* ... */); // May throw an exception
    }
}
```

**2. The Event Subscriber (Process Manager):**
```php
// src/Order/Subscriber/OrderPaidSubscriber.php
use Sbooker\DomainEvents\DomainEventSubscriber;
use Sbooker\CommandBus\CommandBus;

final class OrderPaidSubscriber implements DomainEventSubscriber
{
    private CommandBus $commandBus;
    public function handleEvent(DomainEvent $event): void
    {
        if (!$event instanceof OrderPaid) return;
        $command = new ReserveItems($event->getOrderId(), $event->getItems());
        $this.commandBus->accept(Uuid::uuid4(), $command);
    }
    // ...
}
```

### Common Steps: Configuration and Worker Launch

Regardless of the scenario, you need to configure the `Registry` and run a worker.

**1. Configure the Registry:**
```php
// bootstrap.php or your DI container
use Sbooker\CommandBus\Registry\Containerized\ContainerizedRegistry;
use Sbooker\CommandBus\Registry\Containerized\ContainerAdapter;

/** @var Psr\Container\ContainerInterface $container */

$endpointsMap = new ContainerAdapter($container, null, [
    'send-email' => SendEmailEndpoint::class,
    'reserve-items' => ReserveItemsEndpoint::class,
]);
$timeoutsMap = new ContainerAdapter($container, 'default_timeout');
$registry = new ContainerizedRegistry($endpointsMap, $timeoutsMap);
```

**2. Run the Worker:**
```php
// worker.php
/** @var Sbooker\CommandBus\Handler $handler */
while (true) {
    if (!$handler->handleNext()) sleep(5);
}
// It is recommended to use sbooker/event-loop-worker for a more efficient implementation
```

**3. Configure Doctrine:**
You need to provide mappings for the `Sbooker\CommandBus\Command` entity and its embeddables, as well as register a custom type for `Status`.
Or you can use the [ones shipped with the library](./src/Infrastructure/Persistence/Mapping).

**4. Also included:**
*   A console command to manually execute a command by its ID: [ExecuteCommand](./src/Infrastructure/Console/ExecuteCommand.php)
*   A console command to clean up processed commands: [Clean](./src/Infrastructure/Console/Clean.php)

## License
See [LICENSE][license] file.

[badge-release]: https://img.shields.io/packagist/v/sbooker/command-bus.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/sbooker/command-bus.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/sbooker/command-bus.svg?style=flat-square

[release]: https://packagist.org/packages/sbooker/command-bus
[license]: https://github.com/sbooker/command-bus/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/sbooker/command-bus

[doctrine-orm]: https://www.doctrine-project.org/projects/orm.html
[composer]: https://getcomposer.org

 
