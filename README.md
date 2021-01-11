<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast driver for Laravel 5.6 - 8 </h2>

<p align="center">
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge/build-status/main"><img src="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge/badges/build.png?b=master" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifuge/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifuge.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://scrutinizer-ci.com/g/denis660/laravel-centrifuge"><img src="https://img.shields.io/scrutinizer/g/denis660/laravel-centrifuge.svg?style=flat-square" alt="Quality Score"></a>
<a href="https://github.styleci.io/repos/324202212"><img src="https://github.styleci.io/repos/324202212/shield?branch=master" alt="StyleCI"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifuge"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifuge.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://github.com/denis660/laracent/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Introduction
Centrifugo broadcaster for laravel >= 8 is fork of [laracent](https://github.com/AlexHnydiuk/laracent), based on:
- [LaraComponents/centrifuge-broadcaster](https://github.com/LaraComponents/centrifuge-broadcaster)
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Changenotes
- support Laravel 5.6 - 8
- support guzzlehttp/guzzle 6-7

## Requirements

- PHP >= 7.3
- Laravel 5.6 - 8
- Centrifugo Server 2.8.1 or newer (see [here](https://github.com/centrifugal/centrifugo))

## Dependencies

- guzzlehttp/guzzle 6 - 7

## Installation

Require this package with composer:

```bash
composer require denis660/laravel-centrifuge
```

Open your config/app.php and add the following to the providers array:

```php
'providers' => [
    // ...
    denis660\Laracent\LaracentServiceProvider::class,

    // And uncomment BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Open your config/broadcasting.php and add new connection like this:

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

Also you should add these two lines to your .env file:

```
CENTRIFUGO_SECRET=token_hmac_secret_key-from-centrifugo-config
CENTRIFUGO_APIKEY=api_key-from-centrifugo-config
```

These lines are optional:
```
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGO_VERIFY=false
```

Don't forget to change `BROADCAST_DRIVER` setting in .env file!

```
BROADCAST_DRIVER=centrifugo
```

## Basic Usage

To configure Centrifugo server, read [official documentation](https://centrifugal.github.io/centrifugo/)

For broadcasting events, see [official documentation of laravel](https://laravel.com/docs/8.x/broadcasting)

A simple client usage example:

```php
<?php

namespace App\Http\Controllers;

use denis660\Laracent\Laracent;

class ExampleController extends Controller
{
    public function home(Laracent $centrifugo)
    {
        // Send message into channel
        $centrifugo->publish('channel-name', [
            'key' => 'value'
        ]);

        // Generate connection token
        $token = $centrifugo->generateConnectionToken('user id', 'timestamp', 'info');

        // Generate private channel token
        $apiSign = $centrifuge->generatePrivateChannelToken('client', 'channel', 'timestamp', 'info');

        // ...
    }
}
```

### Available methods

| Name | Description |
|------|-------------|
| publish(string $channel, array $data) | Send message into channel. |
| broadcast(array $channels, array $data) | Send message into multiple channel. |
| presence(string $channel) | Get channel presence information (all clients currently subscribed on this channel). |
| presence_stats(string $channel) | Get channel presence information in short form (number of clients).|
| history(string $channel) | Get channel history information (list of last messages sent into channel). |
| history_remove(string $channel) | Remove channel history information.
| unsubscribe(string $channel,  string $user) | Unsubscribe user from channel. |
| disconnect(string $user_id) | Disconnect user by it's ID. |
| channels() | Get channels information (list of currently active channels). |
| info() | Get stats information about running server nodes. |
| generateConnectionToken(string $userId, int $exp, array $info)  | Generate connection token. |
| generatePrivateChannelToken(string $client, string $channel, int $exp, array $info) | Generate private channel token. |

## License

The MIT License (MIT). Please see [License File](https://github.com/LaraComponents/centrifuge-broadcaster/blob/master/LICENSE) for more information.
