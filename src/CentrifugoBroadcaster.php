<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use denis660\Centrifugo\Dto\CentrifugoTokenDataDto;
use Exception;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CentrifugoBroadcaster extends Broadcaster
{
    public const CONNECTION_CHANNEL = 'App';

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
     *
     * @return mixed
     */
    public function auth($request)
    {
        $channels = $this->getChannelsFromRequest($request);

        if (empty($channels)) {
            return response()->json([
                static::CONNECTION_CHANNEL => $this->generateTokenFromChannelCallbackResult(
                    $request,
                    static::CONNECTION_CHANNEL
                )
            ]);
        }

        $tokens = Arr::mapWithKeys(
            $channels,
            function ($channel) use ($request) {
                return [
                    $channel => $this->generateTokenFromChannelCallbackResult(
                        $request,
                        $channel,
                    )
                ];
            }
        );

        return response()->json($tokens);
    }

    protected function generateTokenFromChannelCallbackResult(
        Request $request,
        string $channel
    ): string {
        try {
            $result = $this->verifyUserCanAccessChannel(
                $request,
                $this->normalizeChannelName($channel)
            );
        } catch (AccessDeniedHttpException $e) {
            return '';
        }

        $tokenDto = $this->prepareTokenDtoFromChannelCallbackResult(
            $result,
            $request,
            $channel
        );

        if ($channel === self::CONNECTION_CHANNEL) {
            return $this->centrifugo->generateConnectionToken(
                $tokenDto->userId,
                $tokenDto->expireSeconds,
                $tokenDto->info,
                [$channel]
            );
        }

        return $this->centrifugo->generatePrivateChannelToken(
            $tokenDto->userId,
            $channel,
            $tokenDto->expireSeconds,
            $tokenDto->info
        );
    }

    protected function prepareTokenDtoFromChannelCallbackResult(
        $result,
        Request $request,
        string $channel
    ): CentrifugoTokenDataDto {
        if ($result instanceof CentrifugoTokenDataDto) {
            return $result;
        }

        if (is_array($result)) {
            if (isset($result['userId'], $result['info'])) {
                return new CentrifugoTokenDataDto(
                    userId: $result['userId'],
                    info: $result['info'],
                    expireSeconds: $result['expireSeconds'] ?? 0
                );
            }

            return new CentrifugoTokenDataDto(
                userId: $this->retrieveUserId($request, $channel),
                info: $result,
                expireSeconds: 0,
            );
        }

        return new CentrifugoTokenDataDto(
            userId: $this->retrieveUserId($request, $channel),
            info: [],
            expireSeconds: 0
        );
    }

    protected function retrieveUserId(Request $request, string $channel): ?string
    {
        $user = $this->retrieveUser($request, $channel);
        return (string)($user?->getAuthIdentifier() ?? $request->ip());
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

        $response = $this->centrifugo->broadcast($this->formatChannels($channels), $payload);

        if (!isset($response['error'])) {
            return;
        }

        throw new BroadcastException(
            $response['error'] instanceof Exception
                ? $response['error']->getMessage()
                : $response['error']
        );
    }

    /**
     * Get channels from request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    private function getChannelsFromRequest(Request $request): array
    {
        $channels = $request->channel
            ?: $request->channel_name
            ?: $request->channels
            ?: $request->c;

        if (!is_array($channels)) {
            $channels = explode(',', (string)$channels);
        }

        return collect(Arr::map(
            $channels,
            'trim'
        ))->filter()->toArray();
    }

    /**
     * Get channel name without $ symbol (if present).
     *
     * @param string $channel
     *
     * @return string
     */
    private function normalizeChannelName(string $channel): string
    {
        return $this->isPrivateChannel($channel) ? mb_substr($channel, 1) : $channel;
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
        return str_starts_with($channel, '$');
    }
}
