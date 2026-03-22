<p align="center">Документация <a href="https://github.com/denis660/laravel-centrifugo/blob/master/README.md">EN</a> | <b>RU</b></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast драйвер для Laravel 9 - 13 </h2>

<p align="center">
<a href="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml"><img src="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifugo/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifugo.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifugo.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/php-v/denis660/laravel-centrifugo" alt="PHP Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/v/denis660/laravel-centrifugo" alt="Laravel Version"></a>
<a href="https://github.com/denis660/laravel-centrifugo/blob/master/build/logs/clover.xml"><img src="https://img.shields.io/badge/coverage-100%25-brightgreen?style=flat-square" alt="Coverage"></a>
<a href="https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Введение
Centrifugo broadcaster для laravel , основан на :
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Особенности
- Совместимо с Centrifugo v6, проверено до [v6.7.0](https://github.com/centrifugal/centrifugo/releases/tag/v6.7.0) 🚀
- Обертка над [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- Аутентификация с помощью токена JWT (HMAC алгоритм) для анонимного, авторизованного пользователя и приватного канала 🗝️

## Требования
- PHP 8.0 - 8.4
- Laravel 9 - 13
- Guzzlehttp/Guzzle 6 - 7
- Centrifugo Сервер v6 или новее (см. [здесь](https://github.com/centrifugal/centrifugo))

## Установка
##### Выберите нужную вам версию

| Версия  |   PHP    |     Laravel     | Centrifugo | Примечания        |
|:-------:|:--------:|:---------------:|:----------:|:-------------------|
| `5.*` | `>= 8.0` |   `9` - `13`    |     `5-6`      | **Текущая версия** |
| `3.0.*` | `>= 7.4` | `8.75.*` - `10` |   `4`-`5`   | Предыдущая версия  |

Установите пакет:

```bash
composer require denis660/laravel-centrifugo
```

Затем запустите установщик:

```bash
php artisan centrifuge:install
```

Установщик:
- добавит соединение `centrifugo` в `config/broadcasting.php`
- добавит необходимые переменные `CENTRIFUGO_*` в `.env`
- установит и `BROADCAST_DRIVER=centrifugo`, и `BROADCAST_CONNECTION=centrifugo`
- предложит запустить `php artisan install:broadcasting`, если broadcasting scaffolding еще не установлен

В новых приложениях Laravel 11-13 вещание по умолчанию отключено, поэтому обычно проще всего позволить `centrifuge:install` включить его автоматически.

## Конфигурация
После выполнения `centrifuge:install` замените сгенерированные значения в `.env` на реальные данные вашего сервера Centrifugo.

# Учетные данные
Для установления соединения с Centrifugo необходимо задать учетные данные из конфигурации вашего сервера Centrifugo. Во время установки пакет создаст локальные значения-заглушки, но их нужно заменить на реальные данные вашего сервера.

Обязательные параметры
```
CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=token_hmac_secret_key-from-centrifugo-config
CENTRIFUGO_API_KEY=api_key-from-centrifugo-config
```

Эти строки необязательны, изменять если необходимо:
```
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGO_VERIFY=false
```

Установщик настраивает обе переменные broadcasting в `.env`:

```
BROADCAST_DRIVER=centrifugo
BROADCAST_CONNECTION=centrifugo
```

## Клиентские SDK
Для работы с клиентом , почитайте в [Client SDK API](https://centrifugal.dev/docs/transports/client_api)

Вот список SDK, поддерживаемых Centrifugal Labs:
- [JavaScript](https://github.com/centrifugal/centrifuge-js) — для браузера, NodeJS и React Native
- [Golang](https://github.com/centrifugal/centrifuge-go) — для языка Go
- [Dart](https://github.com/centrifugal/centrifuge-dart) — для Dart и Flutter (мобильные и веб-приложения)
- [Swift](https://github.com/centrifugal/centrifuge-swift) — для собственной разработки iOS
- [Java](https://github.com/centrifugal/centrifuge-java) — для собственной разработки Android и общей Java
- [Python](https://github.com/centrifugal/centrifuge-python) — SDK реального времени для Python поверх asyncio

## Базовое использование
Настройте ваш сервер Centrifugo , детальнее в [официальной документации](https://centrifugal.dev)
Для отправки событий, почитайте [официальную документацию для Laravel](https://laravel.com/docs/13.x/broadcasting)

## Пример Broadcast Driver
Пакет можно использовать не только как обертку над HTTP API, но и как полноценный Laravel broadcasting driver.

### 1. Создайте broadcast-событие

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShipmentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $orderId,
        public string $status,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("orders.{$this->orderId}")];
    }

    public function broadcastAs(): string
    {
        return 'shipment.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'status' => $this->status,
        ];
    }
}
```

### 2. Разрешите доступ к приватному каналу

```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.{orderId}', function ($user, int $orderId) {
    return (int) $user->id === (int) $orderId; // Замените на свое правило доступа.
});
```

### 3. Отправьте событие

```php
OrderShipmentStatusUpdated::dispatch($order->id, 'packed');
```

### 4. Отдайте клиенту connection token для Centrifugo

```php
<?php

use denis660\Centrifugo\Centrifugo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/centrifugo/connection-token', function (Request $request, Centrifugo $centrifugo) {
    return [
        'token' => $centrifugo->generateConnectionToken(
            (string) $request->user()->getAuthIdentifier(),
            300,
        ),
    ];
});
```

### 5. Подключитесь и подпишитесь на канал на клиенте

```js
import { Centrifuge } from 'centrifuge';

const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  .getAttribute('content');

const centrifuge = new Centrifuge('ws://127.0.0.1:8000/connection/websocket', {
  getToken: async () => {
    const response = await fetch('/centrifugo/connection-token', {
      headers: {
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
    });

    const data = await response.json();

    return data.token;
  },
});

let clientId = '';
let subscribed = false;
const orderId = 123;

centrifuge.on('connected', (ctx) => {
  clientId = ctx.client;

  if (!subscribed) {
    subscription.subscribe();
    subscribed = true;
  }
});

const subscription = centrifuge.newSubscription(`$orders.${orderId}`, {
  getToken: async (ctx) => {
    const response = await fetch('/broadcasting/auth', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        client: clientId,
        channels: [ctx.channel],
      }),
      credentials: 'same-origin',
    });

    const data = await response.json();

    return data.channels[0].token;
  },
});

subscription.on('publication', (ctx) => {
  console.log(ctx.data.event, ctx.data);
});

centrifuge.connect();
```

Когда Laravel отправляет событие в `new PrivateChannel('orders.123')`, внутри Laravel канал называется `private-orders.123`. Этот драйвер автоматически преобразует его в `$orders.123` для Centrifugo, поэтому на клиенте нужно подписываться именно на канал вида `$...`.

## Прямое использование API
Простой пример использования клиента:

```php
<?php
declare(strict_types = 1);

namespace App\Http\Controllers;

use denis660\Centrifugo\Centrifugo;
use Illuminate\Support\Facades\Auth;

class ExampleController
{
    public function example(Centrifugo $centrifugo)
    {
        // Отправить сообщение в канал news
        $centrifugo->publish('news', ['message' => 'Hello world']);

        // Сгенерировать токен для подключения
        $token = $centrifugo->generateConnectionToken((string)Auth::id(), 0, [
            'name' => Auth::user()->name,
        ]);

        // Сгенерировать токен для подключения к приватному каналу с TTL 5 минут
        $privateToken = $centrifugo->generatePrivateChannelToken((string)Auth::id(), 'channel', 5 * 60, [
            'name' => Auth::user()->name,
        ]);

        //Получить список активных каналов
        $centrifugo->channels();

        //Получить информацию о канале news, список активных клиентов
        $centrifugo->presence('news');
    }
}
```

### Методы для генерации клиентских токенов
| Название | Описание |
|------|-------------|
| ```generateConnectionToken```  | Генерация токена для подключения |
| ```generatePrivateChannelToken``` | Генерация приватного токена для приватного канала |

Аргумент `exp` - это TTL в секундах. Для токена на 5 минут нужно передавать `300`, а не `time() + 300`.

### Методы API

| Название | Описание |
|------|-------------|
| ```publish``` | Отправка сообщения в канал |
| ```broadcast``` | Отправить сообщение в несколько каналов. |
| ```presence``` | Получите информацию о присутствии в канале (все клиенты в настоящее время подписаны на этот канал). |
| ```presenceStats``` | Получите краткую информацию о канале (количество клиентов).|
| ```history``` | Получить информацию об истории канала (список последних сообщений, отправленных в канал). |
| ```historyRemove``` | Удалить информацию из истории канала. |
| ```subscribe``` | Подписать пользователя на канал |
| ```unsubscribe``` | Отписать пользователя от канала. |
| ```disconnect``` | Отключить пользователя по его ID. |
| ```channels``` | Cписок текущих активных каналов. |
| ```info``` | Статистическая информация о запущенных серверных узлах. |

## Лицензия

Лицензия MIT. Пожалуйста прочитайте [License File](https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE) для получения дополнительной информации.

# Помочь проекту
Кошелек USDT: ```TUYJrA9VRtXhDFooESHyT8dQSyg5zmtUg7```

Сеть: ```TRC20```

## Contributing 🤝
Issues и pull request приветствуются.

Перед открытием PR:
- запустите `composer test`
- обновите и `README.md`, и `README_RU.md`, если менялись шаги установки или публичное поведение пакета
- сохраняйте совместимость с заявленными версиями PHP и Laravel
