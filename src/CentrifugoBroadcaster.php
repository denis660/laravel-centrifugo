<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use denis660\Centrifugo\Contracts\CentrifugoInterface;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class CentrifugoBroadcaster extends Broadcaster
{
    /**
     * The Centrifugo SDK instance.
     *
     * @var \denis660\Centrifugo\Contracts\CentrifugoInterface
     */
    protected CentrifugoInterface $centrifugo;

    /**
     * Create a new broadcaster instance.
     *
     * @param \denis660\Centrifugo\Contracts\CentrifugoInterface $centrifugo
     */
    public function __construct(CentrifugoInterface $centrifugo)
    {
        $this->centrifugo = $centrifugo;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function auth($request)
    {
        if ($request->user()) {
            $client = $this->getClientFromRequest($request);
            $channels = $this->getChannelsFromRequest($request);

            $response = [];
            foreach ($channels as $channel) {
                $channelName = $this->getChannelName($channel);

                try {
                    $is_access_granted = $this->verifyUserCanAccessChannel($request, $channelName);
                } catch (HttpException $e) {
                    $is_access_granted = false;
                }

                if ($this->isPrivateChannel($channel)) {
                    $response['channels'][] = $this->makeResponseForPrivateClient($is_access_granted, $channel, $client);
                } else {
                    $response[$channel] = $this->makeResponseForClient($is_access_granted, $client);
                }
            }

            return response($response);
        } else {
            throw new HttpException(401);
        }
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $result
     *
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param array  $channels
     * @param string $event
     * @param array  $payload
     *
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['event'] = $event;
        $channels = array_map(function ($channel) {
            return str_replace('private-', '$', (string) $channel);
        }, array_values($channels));

        try {
            $response = $this->centrifugo->broadcast($this->formatChannels($channels), $payload);
        } catch (Throwable $exception) {
            throw new BroadcastException($exception->getMessage(), 0, $exception);
        }

        if (is_array($response) && !isset($response['error'])) {
            return;
        }

        $error = $response['error'] ?? 'Unknown Centrifugo broadcast error.';

        throw new BroadcastException(
            $error instanceof Throwable ? $error->getMessage() : (string) $error
        );
    }

    /**
     * Get client from request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     */
    private function getClientFromRequest($request)
    {
        return $request->get('client', '');
    }

    /**
     * Get channels from request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    private function getChannelsFromRequest($request)
    {
        $channels = $request->get('channels', []);

        return is_array($channels) ? $channels : [$channels];
    }

    /**
     * Get channel name without $ symbol (if present).
     *
     * @param string $channel
     *
     * @return string
     */
    private function getChannelName(string $channel)
    {
        return $this->isPrivateChannel($channel) ? substr($channel, 1) : $channel;
    }

    /**
     * Check channel name by $ symbol.
     *
     * @param string $channel
     *
     * @return bool
     */
    private function isPrivateChannel(string $channel): bool
    {
        return substr($channel, 0, 1) === '$';
    }

    /**
     * Make response for client, based on access rights.
     *
     * @param bool   $access_granted
     * @param string $client
     *
     * @return array
     */
    private function makeResponseForClient(bool $access_granted, string $client)
    {
        $info = [];

        return $access_granted ? [
            'sign' => $this->centrifugo->generateConnectionToken($client, 0, $info),
            'info' => $info,
        ] : [
            'status' => 403,
        ];
    }

    /**
     * Make response for client, based on access rights of private channel.
     *
     * @param bool   $access_granted
     * @param string $channel
     * @param string $client
     *
     * @return array
     */
    private function makeResponseForPrivateClient(bool $access_granted, string $channel, string $client)
    {
        $info = [];

        return $access_granted ? [
            'channel' => $channel,
            'token'   => $this->centrifugo->generatePrivateChannelToken($client, $channel, 0, $info),
            'info'    => $info,
        ] : [
            'channel' => $channel,
            'status' => 403,
        ];
    }
}
