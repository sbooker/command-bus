[Read in English](README.EN.MD)

# Asynchronous Command Bus (`sbooker/command-bus`)

[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
[![Total Downloads][badge-downloads]][downloads]

Надежная, транзакционная, персистентная шина для асинхронного выполнения команд с поддержкой повторных попыток и отслеживанием состояния.

## Назначение библиотеки

Эта библиотека решает две ключевые задачи в современных приложениях:

**1. Надежное асинхронное выполнение задач.**
Библиотека позволяет **атомарно сохранить команду** на выполнение долгой или ненадежной операции (отправка email, запрос к API) в базу данных. Отдельный фоновый процесс (воркер) гарантированно выполнит эту команду позже.

**2. Создание фундамента для отказоустойчивых Process Managers (Sagas).**
В event driven системах обработка доменных событий может быть хрупкой. Например, если подписчик на событие OrderPaid пытается выполнить операцию Warehouse::reserve() и получает ошибку, вся очередь обработки событий может быть заблокирована.
Выход — сделать обработку событий простой и безошибочной, преобразуя событие или последовательность событий в команду(ы). Обычно для этого используют паттерн **Process Manager (Saga)**.
**Ответственность за преобразование события в команду лежит на вашем коде** (в подписчике на события).
Сама библиотека **не реализует** паттерн Process Manager, но **существенно облегчает его реализацию**, предоставляя **надежный, транзакционный механизм для асинхронного выполнения этой команды**, изолируя сбои и не блокируя обработку других событий.

## Ключевые особенности

*   **Основа для Process Manager / Saga:** Предоставляет надежный механизм выполнения команд, который является ключевой частью реализации Process Manager.
*   **Транзакционная надежность:** Прием команды происходит атомарно благодаря интеграции с [sbooker/transaction-manager](https://github.com/sbooker/transaction-manager).
*   **Декларативные стратегии ретраев:** Логика повторных попыток не находится в обработчике. Она **декларативно настраивается для каждой команды** через `TimeoutCalculator`. Сама персистентная сущность `Command` отслеживает количество попыток и время следующего запуска. Воркер лишь пытается выполнить готовые к запуску команды.
*   **Гарантированное выполнение (At-Least-Once):** Команда будет выполняться до тех пор, пока не завершится успешно или не исчерпает все попытки.
*   **Отслеживание состояния:** В любой момент можно узнать статус команды (`created`, `pending`, `success`, `fail`).
*   **Хуки на результат:** Определяйте колбэки (`Invoker`) на успешное или неуспешное завершение.

## Установка

```bash
composer require sbooker/command-bus
```
Вам также понадобятся зависимости из экосистемы и адаптеры:
```bash
composer require sbooker/transaction-manager sbooker/workflow sbooker/event-loop-worker
composer require sbooker/doctrine-transaction-handler # Если вы используете Doctrine
```

## Быстрый старт

### Сценарий 1: Асинхронное выполнение задачи

**Задача:** Отправить приветственный email, не заставляя пользователя ждать.

**1. Команда и ее Endpoint:**
```php
// src/Mailing/Command/SendEmail.php
final class SendEmail { /* ... DTO с to, subject, body ... */ }

// src/Mailing/Endpoint/SendEmailEndpoint.php
use Sbooker\CommandBus\Endpoint;
final class SendEmailEndpoint implements Endpoint
{
    private Mailer $mailer;
    public function process(UuidInterface $id, object $payload): void
    {
        /** @var SendEmail $payload */
        $this->mailer->send(/* ... */);
    }
}
```

**2. Отправка команды:**
```php
// src/UseCase/RegisterUser/Handler.php
/** @var Sbooker\CommandBus\CommandBus $commandBus */

$command = new SendEmail('welcome@user.com', 'Welcome!', '...');
$commandBus->accept(Uuid::uuid4(), $command); // Быстро и надежно
```

### Сценарий 2 (Продвинутый): Построение Process Manager

**Задача:** После оплаты заказа (`OrderPaid` event) надежно зарезервировать товары на складе.

**1. Команда и ее Endpoint:**
```php
// src/Warehouse/Command/ReserveItems.php
final class ReserveItems { /* ... DTO с orderId, items ... */ }

// src/Warehouse/Endpoint/ReserveItemsEndpoint.php
use Sbooker\CommandBus\Endpoint;
final class ReserveItemsEndpoint implements Endpoint
{
    private WarehouseService $warehouseService;
    public function process(UuidInterface $id, object $payload): void
    {
        /** @var ReserveItems $payload */
        $this->warehouseService->reserve(/* ... */); // Может выбросить исключение
    }
}
```

**2. Подписчик на событие (Process Manager):**
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
        $this->commandBus->accept(Uuid::uuid4(), $command);
    }
    // ...
}
```

### Общие шаги: Конфигурация и запуск воркера

Независимо от сценария, вам нужно настроить `Registry` и запустить воркер.

**1. Конфигурация Registry:**
```php
// bootstrap.php или ваш DI-контейнер
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

**2. Запуск воркера:**
```php
// worker.php
/** @var Sbooker\CommandBus\Handler $handler */
while (true) {
    if (!$handler->handleNext()) sleep(5);
}
// Рекомендуется использовать sbooker/event-loop-worker для более эффективной реализации
```

**3. Настройка Doctrine:**
Вам нужно предоставить маппинг для сущности `Sbooker\CommandBus\Command` и ее `embeddables`, а также зарегистрировать кастомный тип для `Status`.
Или использовать [поставляемые с библиотекой](./src/Infrastructure/Persistence/Mapping)

**4. Так же в комплекте:**
*  Консольная команда для ручного запуска обработки команды по ее идентификатору [ExecuteCommand](./src/Infrastructure/Console/ExecuteCommand.php)
*  Консольная команда для очистки отработанных команд [Clean](./src/Infrastructure/Console/Clean.php)

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
 
