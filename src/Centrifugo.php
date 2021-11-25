<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use denis660\Centrifugo\Contracts\CentrifugoInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;

class Centrifugo implements CentrifugoInterface
{
    const API_PATH = '/api';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $config;

    /**
     * Create a new Centrifugo instance.
     * @param array $config
     * @param HttpClient $httpClient
     */
    public function __construct(array $config, HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->config = $this->initConfiguration($config);
    }

    /**
     * Init centrifugo configuration.
     *
     * @param array $config
     * @return  array
     */
    protected function initConfiguration(array $config)
    {
        $defaults = [
            'url'     => 'http://localhost:8000',
            'secret'  => null,
            'apikey'  => null,
            'ssl_key' => null,
            'verify'  => true,
        ];

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @return  mixed
     */
    public function publish(string $channel, array $data)
    {
        return $this->send('publish', [
            'channel' => $channel,
            'data'    => $data,
        ]);
    }

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @return  mixed
     */
    public function broadcast(array $channels, array $data)
    {
        $params = ['channels' => $channels, 'data' => $data];

        return $this->send('broadcast', $params);
    }

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     * @return  mixed
     */
    public function presence(string $channel)
    {
        return $this->send('presence', ['channel' => $channel]);
    }

    /**
     * Get channel presence information in short form.
     *
     * @param string $channel
     * @return  mixed
     */
    public function presenceStats(string $channel)
    {
        return $this->send('presence_stats', ['channel' => $channel]);
    }

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return  mixed
     */
    public function history(string $channel)
    {
        return $this->send('history', ['channel' => $channel]);
    }

    /**
     * Remove channel history information.
     *
     * @param string $channel
     * @return  mixed
     */
    public function historyRemove(string $channel)
    {
        return $this->send('history_remove', [
            'channel' => $channel,
        ]);
    }

    /**
     * Unsubscribe user from channel.
     *
     * @param string $channel
     * @param string $user
     * @return  mixed
     */
    public function unsubscribe(string $channel, string $user)
    {
        return $this->send('unsubscribe', [
            'channel' => $channel,
            'user'    => $user,
        ]);
    }

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return  mixed
     */
    public function disconnect(string $user_id)
    {
        return $this->send('disconnect', ['user' => (string)$user_id]);
    }

    /**
     * Get channels information (list of currently active channels).
     *
     * @return  mixed
     */
    public function channels()
    {
        return $this->send('channels');
    }

    /**
     * Get stats information about running server nodes.
     *
     * @return  mixed
     */
    public function info()
    {
        return $this->send('info');
    }

    /**
     * Generate connection token.
     *
     * @param string $userId
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generateConnectionToken(string $userId = '', int $exp = 0, array $info = [])
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['sub' => $userId];
        if (!empty($info)) {
            $payload['info'] = $info;
        }
        if ($exp) {
            $payload['exp'] = $exp;
        }
        $segments = [];
        $segments[] = $this->urlsafeB64Encode(json_encode($header));
        $segments[] = $this->urlsafeB64Encode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->getSecret());
        $segments[] = $this->urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Generate private channel token.
     *
     * @param string $client
     * @param string $channel
     * @param int $exp
     * @param array $info
     * @return string
     */
    public function generatePrivateChannelToken(string $client, string $channel, int $exp = 0, array $info = [])
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['channel' => $channel, 'client' => $client];
        if (!empty($info)) {
            $payload['info'] = $info;
        }
        if ($exp) {
            $payload['exp'] = $exp;
        }
        $segments = [];
        $segments[] = $this->urlsafeB64Encode(json_encode($header));
        $segments[] = $this->urlsafeB64Encode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->getSecret());
        $segments[] = $this->urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Get secret key.
     *
     * @return string
     */
    protected function getSecret()
    {
        return $this->config['secret'];
    }

    /**
     * Send message to centrifugo server.
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    protected function send($method, array $params = [])
    {
        $json = json_encode(['method' => $method, 'params' => $params]);

        $headers = [
            'Content-type'  => 'application/json',
            'Authorization' => 'apikey ' . $this->config['apikey'],
        ];

        try {
            $url = parse_url($this->prepareUrl());

            $config = collect([
                'headers'     => $headers,
                'body'        => $json,
                'http_errors' => true,
            ]);

            if ($url['scheme'] == 'https') {
                $config->put('verify', collect($this->config)->get('verify', false));

                if (collect($this->config)->get('ssl_key')) {
                    $config->put('ssl_key', collect($this->config)->get('ssl_key'));
                }
            }

            $response = $this->httpClient->post($this->prepareUrl(), $config->toArray());

            $result = json_decode((string)$response->getBody(), true);
        } catch (ClientException $e) {
            $result = [
                'method' => $method,
                'error'  => $e->getMessage(),
                'body'   => $params,
            ];
        }

        return $result;
    }

    /**
     * Prepare URL to send the http request.
     *
     * @return string
     */
    protected function prepareUrl()
    {
        $address = rtrim($this->config['url'], '/');

        if (substr_compare($address, static::API_PATH, -strlen(static::API_PATH)) !== 0) {
            $address .= static::API_PATH;
        }
        //$address .= '/';

        return $address;
    }

    /**
     * Safely encode string in base64.
     * @param string $input
     * @return string
     */
    private function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Sign message with secret key.
     * @param string $msg
     * @param string $key
     * @return string
     */
    private function sign($msg, $key)
    {
        return hash_hmac('sha256', $msg, $key, true);
    }
}
