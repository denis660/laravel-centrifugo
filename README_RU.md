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
- Совместимость с последней версией [Centrifugo v6.2.3](https://github.com/centrifugal/centrifugo/releases/tag/v6.2.3) 🚀
- Обертка над [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- Аутентификация с помощью токена JWT (HMAC алгоритм) для анонимного, авторизованного пользователя и приватного канала 🗝️

## Требования
- PHP 8.0 - 8.4
- Laravel 9 - 13
- Guzzlehttp/Guzzle 6 - 7
- Centrifugo Сервер v6 или новее (см. [здесь](https://github.com/centrifugal/centrifugo))

## Установка

Для Laravel 9-10
```bash
composer require denis660/laravel-centrifugo
```
Для Laravel 11-13 есть особенности, читаем ниже



##### Выберите нужную вам версию

| Версия  |   PHP    |     Laravel     | Centrifugo | Примечания        |
|:-------:|:--------:|:---------------:|:----------:|:-------------------|
| `5.*` | `>= 8.0` |   `9` - `13`    |     `5-6`      | **Текущая версия** |
| `3.0.*` | `>= 7.4` | `8.75.*` - `10` |   `4`-`5`   | Предыдущая версия  |


По умолчанию вещание не включено в новых приложениях Laravel 11-13. Вы можете включить вещание с помощью команды install:broadcasting Artisan:
```bash
php artisan install:broadcasting
```
Если будет вопрос, надо ли установить Reverb, отвечаем - нет

Потом установить пакет для работы с Centrifuge через composer, выполнив команду в консоле:
```bash
composer require denis660/laravel-centrifugo
```

## Конфигурация
Запустите команду `centrifuge:install`, которая опубликует файл конфигурации и сгенерирует необходимые переменные окружения в вашем файле `.env` со значениями по умолчанию.
```bash
php artisan centrifuge:install
```

# Учетные данные
Для установления соединения с Centrifugo необходимо задать набор учетных данных из файла `config.json` вашего сервера Centrifugo.
Laravel сгенерирует примеры ключей, которые вы должны заменить на реальные учетные данные вашего сервера. Вы можете определить эти учетные данные с помощью следующих переменных среды:

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
### Методы для генерации клиентских токенов
| Название | Описание |
|------|-------------|
| ```generateConnectionToken```  | Генерация токена для подключения |
| ```generatePrivateChannelToken``` | Генерация приватного токена для приватного канала |


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
