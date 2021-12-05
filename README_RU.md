<p align="center">Документация <a href="https://github.com/denis660/laravel-centrifugo/blob/master/README.md">EN</a> | <b>RU</b></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast драйвер для Laravel 7.30.4 - 8 </h2>

<p align="center">
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifugo/build-status/main"><img src="https://scrutinizer-ci.com/g/denis660/laravel-centrifugo/badges/quality-score.png?b=master" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifugo/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifugo.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifugo"><img src="https://img.shields.io/scrutinizer/g/denis660/laravel-centrifugo.svg?style=flat-square" alt="Quality Score"></a>
<a href="https://github.styleci.io/repos/324202212"><img src="https://github.styleci.io/repos/324202212/shield?branch=master" alt="StyleCI"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifugo.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/denis660/Centrifuge/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Введение
Centrifugo broadcaster для laravel , основан на :
- [LaraComponents/centrifugo-broadcaster](https://github.com/LaraComponents/centrifugo-broadcaster)
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Особенности
- Совместимость с последней версией [Centrifugo 3.1.0](https://github.com/centrifugal/centrifugo/releases/tag/v3.1.0) 🚀
- Обертка над [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- Аутентификация с помощью токена JWT (HMAC алгоритм) для анонимного, авторизованного пользователя и приватного канала 🗝️

## Требования
- PHP >= 7.3, 8.0, 8.1
- Framework Laravel 7.30.4 - 8.6.8
- guzzlehttp/guzzle 6 - 7
- Centrifugo Сервер 3.1.0 или новее (см. [здесь](https://github.com/centrifugal/centrifugo))

## Установка
Установить через composer, выполнив команду в консоле:

```bash
composer req denis660/laravel-centrifugo
```

Откройте ваш config/app.php и добавьте соелубщее в раздел providers:

```php
'providers' => [
    // Раскомментируйте эту строчку BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Откройте ваш config/broadcasting.php и добавьте туда новое подключение:

```php
        'centrifugo' => [
            'driver' => 'centrifugo',
            'token_hmac_secret_key'  => env('CENTRIFUGO_TOKEN_HMAC_SECRET_KEY',''),
            'api_key'  => env('CENTRIFUGO_API_KEY',''),
            'url'     => env('CENTRIFUGO_URL', 'http://localhost:8000'), // centrifugo api url
            'verify'  => env('CENTRIFUGO_VERIFY', false), // Verify host ssl if centrifugo uses this
            'ssl_key' => env('CENTRIFUGO_SSL_KEY', null), // Self-Signed SSl Key for Host (require verify=true)
        ],
```

Также вы должны добавить эти две строчки в ваш .env файл:
```
CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=token_hmac_secret_key-from-centrifugo-config
CENTRIFUGO_API_KEY=api_key-from-centrifugo-config
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

Настройте ваш сервер Centrifugo , детальнее в [официальной документации](https://centrifugal.dev)

Для отправки событий, почитайте [официальную документацию для Laravel](https://laravel.com/docs/8.x/broadcasting)

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

        // Сгенерировать токен для подключения к приватному каналу
        $apiSign = $centrifugo->generatePrivateChannelToken((string)Auth::id(), 'channel', time() + 5 * 60, [
            'name' => Auth::user()->name,
        ]);

        //Получить список активных каналов
        $centrifugo->channels();

        //Получить информацию о канале news, список активных клиентов
        $centrifugo->presence('news');

    }
}
```

### Методы

| Название | Описание |
|------|-------------|
| publish(string $channel, array $data, $skipHistory = false) | Отправка сообщения в канал |
| broadcast(array $channels, array $data, $skipHistory = false) | Отправить сообщение в несколько каналов. |
| presence(string $channel) | Получите информацию о присутствии в канале (все клиенты в настоящее время подписаны на этот канал). |
| presenceStats(string $channel) | Получите краткую информацию о канале (количество клиентов).|
| history(string $channel, $limit = 0, $since = [], $reverse = false) | Получить информацию об истории канала (список последних сообщений, отправленных в канал). |
| historyRemove(string $channel) | Удалить информацию из истории канала. |
| subscribe(string $channel,  string $user, $client = '') | Подписать пользователя на канал |
| unsubscribe(string $channel, string $user, string $client = '') | Отписать пользователя от канала. |
| disconnect(string $user_id) | Отключить пользователя по его ID. |
| channels(string $pattern = '') | Cписок текущих активных каналов. |
| info() | Статистическая информация о запущенных серверных узлах. |
| generateConnectionToken(string $userId = '', int $exp = 0, array $info = [], array $channels = [])  | Генерация токена для подключения |
| generatePrivateChannelToken(string $client, string $channel, int $exp = 0, array $info = []) | Генерация приватного токена для приватного канала |

## Лицения

Лицензия MIT. Пожалуйста прочитайте [License File](https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE) для получения дополнительной информации.
