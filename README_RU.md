<p align="center">Документация <a href="https://github.com/denis660/laravel-centrifugo/blob/master/README.md">EN</a> | <b>RU</b></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast драйвер для Laravel 8.75.0 - 11 </h2>

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
- Совместимость с последней версией [Centrifugo v5.4.5](https://github.com/centrifugal/centrifugo/releases/tag/v5.4.5) 🚀
- Обертка над [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- Аутентификация с помощью токена JWT (HMAC алгоритм) для анонимного, авторизованного пользователя и приватного канала 🗝️

## Требования
- PHP >= 8.2, 8.3
- Framework Laravel 8.75.0 - 11.0
- Guzzlehttp/Guzzle 6 - 7
- Centrifugo Сервер v5.4.5 или новее (см. [здесь](https://github.com/centrifugal/centrifugo))

## Установка

##### Выберите нужную вам версию

| Версия  (X.Y.Z) |   PHP    | Laravel |       Комментарий       |
|:----------------------:|:--------:|:-------:|:--------------------|
|        `4.0.*`         | `>= 8.0` | `11.*`   | **Текущая версия** |
|        `3.0.*`         | `>= 7.4` | `8.75.*`   | Предыдущая версия    |


По умолчанию вещание не включено в новых приложениях Laravel 11. Вы можете включить вещание с помощью команды install:broadcasting Artisan:
```bash
php artisan install:broadcasting
```
Если будет вопрос, надо ли установить Reverb, отвечаем - нет

Потом установить пакет для работы с Centrifuge через composer, выполнив команду в консоле:
```bash
composer require denis660/laravel-centrifugo
```

## Конфигурация
Запустите команду centrifuge:install, которая установит centrifuge-laravel с разумным набором параметров конфигурации по умолчанию и сгенерирует ключи по умолчанию.
Если вы хотите внести какие-либо изменения в конфигурацию, вы можете сделать это, обновив переменные среды centrifuge-laravel или обновив переменные среды в .env.
```bash
php artisan centrifuge:install
```

# Учетные данные
Для установления соединения с Centrifuge необходимо задать набор учетных данных Centrifuge и файла config.json.
Эти учетные данные настраиваются на сервере Centrifuge, но Laravel для примера сгенерирует ключи, обязательно замените их. Вы можете определить эти учетные данные с помощью следующих переменных среды:

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

Не забудьте проверить параметр `BROADCAST_DRIVER` в файле .env!

```
BROADCAST_DRIVER=centrifugo
```

## Базовое использование

Настройте ваш сервер Centrifugo , детальнее в [официальной документации](https://centrifugal.dev)

Для отправки событий, почитайте [официальную документацию для Laravel](https://laravel.com/docs/11.x/broadcasting)

Для работы с клиентом на сайте с использованием JavaScript, почитайте в [Client SDK API](https://centrifugal.dev/docs/transports/client_api)

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
