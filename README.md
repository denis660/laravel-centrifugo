<p align="center">Documentation <b>EN</b> | <a href="https://github.com/denis660/laravel-centrifugo/blob/master/README_RU.md">RU</a></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast driver for Laravel 9 - 12</h2>

<p align="center">
<a href="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml"><img src="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifugo/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifugo.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifugo.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/php-v/denis660/laravel-centrifugo" alt="PHP Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/v/denis660/laravel-centrifugo" alt="Laravel Version"></a>
<a href="https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Introduction
Centrifugo broadcaster for Laravel is based on:
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Features
- Compatibility with the latest version of [Centrifugo v6.2.3](https://github.com/centrifugal/centrifugo/releases/tag/v6.2.3) 🚀
- Wrapper for [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- JWT token authentication (HMAC algorithm) for anonymous, authorized users, and private channels 🗝️

## Requirements
- PHP 8.0 - 8.4
- Laravel 9 - 12
- Guzzlehttp/Guzzle 6 - 7
- Centrifugo Server v6 or newer (see [here](https://github.com/centrifugal/centrifugo))

## Installation
For Laravel 9-10:

```bash
composer require denis660/laravel-centrifugo
```

For Laravel 11-12, there are specific instructions below.

##### Select the version you need

| Version |   PHP    |     Laravel     | Centrifugo | Notes               |
|:-------:|:--------:|:---------------:|:----------:|:--------------------|
|  `5.*`  | `>= 8.0` |   `9` - `12`    |   `5-6`    | **Current version** |
| `3.0.*` | `>= 7.4` | `8.75.*` - `10` |  `4`-`5`	  | Previous version    |


By default, broadcasting is disabled in new Laravel 11 applications. You can enable broadcasting using the install
Artisan command:
```bash
php artisan install:broadcasting
```
If asked whether to install Reverb, answer “no.”

Then, install the package for working with Centrifugo via composer by running the following command:
```bash
composer require denis660/laravel-centrifugo
```

## Configuration
Run the `centrifuge:install` command, which will publish the configuration file and generate the necessary environment variables in your `.env` file with default values.

```bash
php artisan centrifuge:install
```

# Credentials
To establish a connection with Centrifugo, you need to provide credentials from your Centrifugo server's `config.json` file. Laravel will generate example keys that you should replace with your actual server credentials. You can specify these credentials with the following environment variables:

Required parameters:
```
CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=token_hmac_secret_key-from-centrifugo-config
CENTRIFUGO_API_KEY=api_key-from-centrifugo-config
```
Optional parameters, modify if needed:
```
CENTRIFUGO_URL=http://localhost:8000
CENTRIFUGO_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGO_VERIFY=false
```

Make sure to check the `BROADCAST_DRIVER` parameter in the .env file:

```
BROADCAST_DRIVER=centrifugo
```

## Client SDKs
For working with clients, see the [Client SDK API](https://centrifugal.dev/docs/transports/client_api)

Here is a list of SDKs supported by Centrifugal Labs:
- [JavaScript](https://github.com/centrifugal/centrifuge-js) — for browser, NodeJS, and React Native
- [Golang](https://github.com/centrifugal/centrifuge-go) — for Go language
- [Dart](https://github.com/centrifugal/centrifuge-dart) — for Dart and Flutter (mobile and web applications)
- [Swift](https://github.com/centrifugal/centrifuge-swift) — for native iOS development
- [Java](https://github.com/centrifugal/centrifuge-java) — for native Android and general Java development
- [Python](https://github.com/centrifugal/centrifuge-python) — real-time SDK for Python on top of asyncio

## Basic Usage

Set up your Centrifugo server as detailed in the [official documentation](https://centrifugal.dev)
For sending events, refer to the [official Laravel documentation](https://laravel.com/docs/11.x/broadcasting)

Here is a simple example of client usage:

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
        // Send a message to the news channel
        $centrifugo->publish('news', ['message' => 'Hello world']);

        // Generate a connection token
        $token = $centrifugo->generateConnectionToken((string)Auth::id(), 0, [
            'name' => Auth::user()->name,
        ]);

        // Generate a token for a private channel connection
        $apiSign = $centrifugo->generatePrivateChannelToken((string)Auth::id(), 'channel', time() + 5 * 60, [
            'name' => Auth::user()->name,
        ]);

        // Get a list of active channels
        $centrifugo->channels();

        // Get information about the news channel and its active clients
        $centrifugo->presence('news');
    }
}
```

### Connection Token Generation
In Centrifugo, connecting to the server requires generating a token, necessitating explicit connection permission. 
This involves configuring a channel for authentication:
```php
// routes/channels.php

use denis660\Centrifugo\CentrifugoBroadcaster;

Broadcast::channel(CentrifugoBroadcaster::CONNECTION_CHANNEL, fn() => true);
```

### Obtaining Broadcasting Authentication Tokens
Laravel's default /broadcasting/auth endpoint enables the retrieval of tokens for secure broadcasting. 
Here's how you can obtain tokens for different channels:
```js
// Fetch token for connection
const connectionToken = await fetch('/broadcasting/auth', {
  method: 'POST',
  headers: { 
    'Content-Type': 'application/json',
  },
}).then((res) => res.json())
  .then((tokens) => tokens['App'] || '')

// Fetch token for a specific channel, e.g., 'Chat'
const chatChannelToken = await fetch('/broadcasting/auth', {
  method: 'POST',
  headers: { 
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    // Specify channel(s) here. 
    // Accepts parameter names: channel, channels, channel_name, or c
    // Channels can be passed as an array or a comma-separated string
    c: 'Chat',
  }),
}).then((res) => res.json())
  .then((tokens) => tokens['Chat'] || '')
```

### Methods for generating client tokens
| Method                            | Description                                    |
|-----------------------------------|------------------------------------------------|
| ```generateConnectionToken```     | Generate a token for connection                |
| ```generatePrivateChannelToken``` | Generate a private token for a private channel |


### API Methods

| Method              | Description                                                                                |
|---------------------|--------------------------------------------------------------------------------------------|
| ```publish```       | Send a message to a channel                                                                |
| ```broadcast```     | Send a message to multiple channels.                                                       |
| ```presence```      | Get presence information for a channel (all clients currently subscribed to this channel). |
| ```presenceStats``` | Get summary information for a channel (number of clients).                                 |
| ```history```       | Get channel history (list of recent messages sent to the channel).                         |
| ```historyRemove``` | Remove channel history.                                                                    |
| ```subscribe```     | Subscribe a user to a channel                                                              |
| ```unsubscribe```   | Unsubscribe a user from a channel.                                                         |
| ```disconnect```    | Disconnect a user by their ID.                                                             |
| ```channels```      | List current active channels.                                                              |
| ```info```          | Statistical information about running server nodes.                                        |


## License

MIT License. Please read the [License File](https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE) for more information.

# Support the Project
USDT wallet: ```TUYJrA9VRtXhDFooESHyT8dQSyg5zmtUg7```

Network: ```TRC20```

## Contributing 🤝

