<?php

namespace denis660\Centrifuge;

use denis660\Centrifuge\Contracts\CentrifugeInterface;
use Exception;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CentrifugeBroadcaster extends Broadcaster
{
    /**
     * The Centrifugo SDK instance.
     *
     * @var \denis660\Centrifuge\Contracts\CentrifugeInterface
     */
    protected $centrifugo;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \denis660\Centrifuge\Contracts\CentrifugeInterface  $centrifugo
     */
    public function __construct(CentrifugeInterface $centrifugo)
    {
        $this->centrifugo = $centrifugo;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
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

                $response[$channel] = $this->makeResponseForClient($is_access_granted, $client);
            }

            return response()->json($response);
        } else {
            throw new HttpException(401);
        }
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['event'] = $event;

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
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getClientFromRequest($request)
    {
        return $request->get('client', '');
    }

    /**
     * Get channels from request.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @param  string  $channel
     * @return string
     */
    private function getChannelName(string $channel)
    {
        return (substr($channel, 0, 1) === '$') ? substr($channel, 1) : $channel;
    }

    /**
     * Make response for client, based on access rights.
     *
     * @param  bool  $access_granted
     * @param  string $client
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
}
