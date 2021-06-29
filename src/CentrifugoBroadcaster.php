<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use Exception;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CentrifugoBroadcaster extends Broadcaster
{
    /**
     * The Centrifugo SDK instance.
     *
     * @var Contracts\CentrifugoInterface
     */
    protected $centrifugo;

    /**
     * Create a new broadcaster instance.
     *
     * @param Centrifugo $centrifugo
     */
    public function __construct(Centrifugo $centrifugo)
    {
        $this->centrifugo = $centrifugo;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function auth($request)
    {
        if ($request->user()) {
            $client = $this->getClientFromRequest($request);
            $channels = $this->getChannelsFromRequest($request);

            $response = [];
            $privateResponse = [];
            foreach ($channels as $channel) {
                $channelName = $this->getChannelName($channel);

                try {
                    $is_access_granted = $this->verifyUserCanAccessChannel($request, $channelName);
                } catch (HttpException $e) {
                    $is_access_granted = false;
                }

                if ($private = $this->isPrivateChannel($channel)) {
                    $privateResponse['channels'][] = $this->makeResponseForPrivateClient($is_access_granted, $channel, $client);
                } else {
                    $response[$channel] = $this->makeResponseForClient($is_access_granted, $client);
                }
            }

            return response($private ? $privateResponse : $response);
        } else {
            throw new HttpException(401);
        }
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param array $channels
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['event'] = $event;
        $channels = array_map(function ($channel) {
            return str_replace('private-', '$', $channel->name);
        }, $channels);

        $response = $this->centrifugo->broadcast($this->formatChannels($channels), $payload);

        if (is_array($response) && ! isset($response['error'])) {
            return;
        }

        throw new BroadcastException(
            $response['error'] instanceof Exception ? $response['error']->getMessage() : $response['error']
        );
    }

    /**
     * Get client from request.
     *
     * @param \Illuminate\Http\Request $request
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
     * @return bool
     */
    private function isPrivateChannel(string $channel): bool
    {
        return substr($channel, 0, 1) === '$';
    }

    /**
     * Make response for client, based on access rights.
     *
     * @param bool $access_granted
     * @param string $client
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
     * @param bool $access_granted
     * @param string $channel
     * @param string $client
     * @return array
     */
    private function makeResponseForPrivateClient(bool $access_granted, string $channel, string $client)
    {
        $info = [];

        return $access_granted ? [

            'channel' => $channel,
            'token' => $this->centrifugo->generatePrivateChannelToken($client, $channel, 0, $info),
            'info' => $this->centrifugo->info(),

        ] : [
            'status' => 403,
        ];
    }
}
