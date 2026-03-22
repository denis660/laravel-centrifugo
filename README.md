<p align="center">Documentation <b>EN</b> | <a href="https://github.com/denis660/laravel-centrifugo/blob/master/README_RU.md">RU</a></p>

<h1 align="center">Laravel + Centrifugo</h1>
<h2 align="center">Centrifugo broadcast driver for Laravel 9 - 13</h2>

<p align="center">
<a href="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml"><img src="https://github.com/denis660/laravel-centrifugo/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://github.com/denis660/laravel-centrifugo/releases"><img src="https://img.shields.io/github/release/denis660/laravel-centrifugo.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/dt/denis660/laravel-centrifugo.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/php-v/denis660/laravel-centrifugo" alt="PHP Version"></a>
<a href="https://packagist.org/packages/denis660/laravel-centrifugo"><img src="https://img.shields.io/packagist/v/denis660/laravel-centrifugo" alt="Laravel Version"></a>
<a href="https://github.com/denis660/laravel-centrifugo/blob/master/build/logs/clover.xml"><img src="https://img.shields.io/badge/coverage-100%25-brightgreen?style=flat-square" alt="Coverage"></a>
<a href="https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Software License"></a>
</p>

## Introduction
Centrifugo broadcaster for Laravel is based on:
- [centrifugal/phpcent](https://github.com/centrifugal/phpcent)

## Features
- Compatible with Centrifugo v6, verified up to [v6.7.0](https://github.com/centrifugal/centrifugo/releases/tag/v6.7.0) 🚀
- Wrapper for [Centrifugo HTTP API](https://centrifugal.dev/docs/server/server_api) 🔌
- JWT token authentication (HMAC algorithm) for anonymous, authorized users, and private channels 🗝️

## Requirements
- PHP 8.0 - 8.4
- Laravel 9 - 13
- Guzzlehttp/Guzzle 6 - 7
- Centrifugo Server v6 or newer (see [here](https://github.com/centrifugal/centrifugo))

## Installation
##### Select the version you need

| Version  |   PHP    |     Laravel     | Centrifugo |       Notes       |
|:-------:|:--------:|:---------------:|:----------:|:--------------------|
| `5.*` | `>= 8.0` |   `9` - `13`    |     `5-6`      | **Current version** |
| `3.0.*` | `>= 7.4` | `8.75.*` - `10` |    `4`-`5` | Previous version    |

Install the package:

```bash
composer require denis660/laravel-centrifugo
```

Then run the installer:

```bash
php artisan centrifuge:install
```

The installer will:
- add the `centrifugo` connection to `config/broadcasting.php`
- add the required `CENTRIFUGO_*` variables to `.env`
- set both `BROADCAST_DRIVER=centrifugo` and `BROADCAST_CONNECTION=centrifugo`
- offer to run `php artisan install:broadcasting` for you if broadcasting scaffolding is missing

In fresh Laravel 11-13 applications, broadcasting is disabled by default, so letting the installer enable it for you is usually the simplest path.

## Configuration
After `centrifuge:install` finishes, replace the generated values in `.env` with the real credentials from your Centrifugo server.

# Credentials
To establish a connection with Centrifugo, you need to provide credentials from your Centrifugo server configuration. The installer generates placeholder values locally, but you should replace them with the actual values from your server.

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

The installer configures both broadcasting environment variables in `.env`:

```
BROADCAST_DRIVER=centrifugo
BROADCAST_CONNECTION=centrifugo
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
For sending events, refer to the [official Laravel documentation](https://laravel.com/docs/13.x/broadcasting)

## Broadcast Driver Example
The package can be used as a Laravel broadcasting driver, not only as a direct HTTP API wrapper.

### 1. Create a broadcast event

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

### 2. Authorize the private channel

```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.{orderId}', function ($user, int $orderId) {
    return (int) $user->id === (int) $orderId; // Replace with your own access rule.
});
```

### 3. Dispatch the event

```php
OrderShipmentStatusUpdated::dispatch($order->id, 'packed');
```

### 4. Expose an endpoint that returns a Centrifugo connection token

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

### 5. Connect and subscribe from the client

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

When Laravel broadcasts to `new PrivateChannel('orders.123')`, Laravel names the channel `private-orders.123`. This driver automatically converts that name to `$orders.123` for Centrifugo, so clients should subscribe to the `$...` channel name.

## Direct API Usage
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

        // Generate a token for a private channel connection with a 5 minute TTL
        $privateToken = $centrifugo->generatePrivateChannelToken((string)Auth::id(), 'channel', 5 * 60, [
            'name' => Auth::user()->name,
        ]);

        // Get a list of active channels
        $centrifugo->channels();

        // Get information about the news channel and its active clients
        $centrifugo->presence('news');
    }
}
```

### Methods for generating client tokens
| Method | Description |
|------|-------------|
| ```generateConnectionToken```  | Generate a token for connection |
| ```generatePrivateChannelToken``` | Generate a private token for a private channel |

The `exp` argument is a TTL in seconds. For a token valid for 5 minutes, pass `300`, not `time() + 300`.

### API Methods

| Method | Description                                                                                            |
|------|-----------------------------------------------------------------------------------------------------|
| ```publish``` | Send a message to a channel                                                                         |
| ```broadcast``` | Send a message to multiple channels.                                                            |
| ```presence``` | Get presence information for a channel (all clients currently subscribed to this channel). |
| ```presenceStats``` | Get summary information for a channel (number of clients).                                        |
| ```history``` | Get channel history (list of recent messages sent to the channel).           |
| ```historyRemove``` | Remove channel history.                                                          |
| ```subscribe``` | Subscribe a user to a channel                                                         |
| ```unsubscribe``` | Unsubscribe a user from a channel.                                                         |
| ```disconnect``` | Disconnect a user by their ID.                                                                   |
| ```channels``` | List current active channels.                                                                   |
| ```info``` | Statistical information about running server nodes.                                            |

## License

MIT License. Please read the [License File](https://github.com/denis660/laravel-centrifugo/blob/master/LICENSE) for more information.

# Support the Project
USDT wallet: ```TUYJrA9VRtXhDFooESHyT8dQSyg5zmtUg7```

Network: ```TRC20```

## Contributing 🤝
Issues and pull requests are welcome.

Before opening a PR:
- run `composer test`
- update both `README.md` and `README_RU.md` if installation steps or public behavior changed
- keep compatibility with the supported PHP and Laravel versions
