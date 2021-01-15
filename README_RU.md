<p align="center">Документация <a href="https://github.com/denis660/laravel-centrifuge/blob/master/README.md">EN</a> | <b>RU</b></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast драйвер для Laravel 5.6 - 8 </h2>

<p align="center">
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge/build-status/main"><img src="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge/badges/build.png?b=master" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifuge/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifuge.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge"><img src="https://img.shields.io/scrutinizer/g/denis660/laravel-centrifuge.svg?style=flat-square" alt="Quality Score"></a>
<a href="https://github.styleci.io/repos/324202212"><img src="https://github.styleci.io/repos/324202212/shield?branch=master" alt="StyleCI"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifuge"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifuge.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/denis660/Centrifuge/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Введение
Centrifugo broadcaster для laravel >= 8 это форк репозитория [Laracent](https://github.com/AlexHnydiuk/Laracent), based on:
- [LaraComponents/centrifuge-broadcaster](https://github.com/LaraComponents/centrifuge-broadcaster)
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Изменения
- поддержка Laravel 5.6 - 8
- поддержка guzzlehttp/guzzle 6-7

## Требования

- PHP >= 7.3
- Framework Laravel 5.6 - 8
- Centrifugo Сервер 2.8.1 или новее (см. [здесь](https://github.com/centrifugal/centrifugo))

## Зависимости

- guzzlehttp/guzzle 6 - 7

## Установка

Установить через composer, выполнив команду в консоле:

```bash
composer req denis660/laravel-centrifuge
```

Откройте ваш config/app.php и добавьте соелубщее в раздел providers:

```php
'providers' => [
    // ...
    denis660\Centrifugo\CentrifugoServiceProvider::class,

    // Раскомментируйте эту строчку BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Откройте ваш config/broadcasting.php и добавьте туда новое подключение:

```php
    'centrifugo' => [
        'driver' => 'centrifugo',
        'secret'  => env('CENTRIFUGO_SECRET'),
        'apikey'  => env('CENTRIFUGO_APIKEY'),
        'url'     => env('CENTRIFUGO_URL', 'http://localhost:8000'), // centrifugo api url
        'verify'  => env('CENTRIFUGO_VERIFY', false), // Verify host ssl if centrifugo uses this
        'ssl_key' => env('CENTRIFUGO_SSL_KEY', null), // Self-Signed SSl Key for Host (require verify=true)
    ],
```

Также вы должны добавить эти две строчки в ваш .env файл:
```
CENTRIFUGO_SECRET=token_hmac_secret_key-from-centrifugo-config
CENTRIFUGO_APIKEY=api_key-from-centrifugo-config
```

Эти строки необязательны:
```
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGO_VERIFY=false
```

Не забудьте изменить параметр `BROADCAST_DRIVER` в файле .env!

```
BROADCAST_DRIVER=centrifugo
```

## Базовое использование

Настройте ваш сервер Centrifugo , детальнее в [официальной документации](https://centrifugal.github.io/centrifugo/)

Для отправки событий, почитайте [официальную документацию для Laravel](https://laravel.com/docs/8.x/broadcasting)

Простой пример использования клиента:

```php
<?php
declare(strict_types=1);

namespace App\Services;

use denis660\Centrifugo\Centrifugo;

class NotificationService
{
    private $centrifugo;

    public function __construct(Centrifugo $centrifugo)
    {
        $this->centrifugo = $centrifugo;
    }

    public function example(): void
    {
        $this->centrifugo->publish('news', ['message' => 'Hello world']);
    }
}
```

### Методы

| Название | Описание |
|------|-------------|
| publish(string $channel, array $data) | Отправка сообщения в канал |
| broadcast(array $channels, array $data) | Отправить сообщение в несколько каналов. |
| presence(string $channel) | Получите информацию о присутствии в канале (все клиенты в настоящее время подписаны на этот канал). |
| presence_stats(string $channel) | Получите краткую информацию о канале (количество клиентов).|
| history(string $channel) | Получить информацию об истории канала (список последних сообщений, отправленных в канал). |
| history_remove(string $channel) | Удалить информацию из истории канала. |
| unsubscribe(string $channel,  string $user) | Отписать пользователя от канала. |
| disconnect(string $user_id) | Отключить пользователя по его ID. |
| channels() | Cписок текущих активных каналов. |
| info() | Статистическая информация о запущенных серверных узлах. |
| generateConnectionToken(string $userId, int $exp, array $info)  | Генерация токена для подключения |
| generatePrivateChannelToken(string $client, string $channel, int $exp, array $info) | Генерация приватного токена для приватного канала |

## Лицения

Лицензия MIT. Пожалуйста прочитайте [License File](https://github.com/LaraComponents/centrifuge-broadcaster/blob/master/LICENSE) для получения дополнительной информации.
