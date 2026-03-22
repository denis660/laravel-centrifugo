<?php

declare(strict_types=1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Centrifugo;
use denis660\Centrifugo\CentrifugoBroadcaster;
use denis660\Centrifugo\Test\TestCase;
use GuzzleHttp\Client;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CentrifugoBroadcasterTest extends TestCase
{
    private CentrifugoBroadcaster $broadcaster;

    public function setUp(): void
    {
        parent::setUp();

        $this->broadcaster = new CentrifugoBroadcaster($this->centrifuge);
    }

    public function testBroadcastPublishesToPublicChannel(): void
    {
        $channel = 'test-public-'.Str::uuid()->toString();

        $this->broadcaster->broadcast([$channel], 'test-event', ['payload' => 'public']);

        $this->assertTrue(true);
    }

    public function testBroadcastPublishesToPrivateChannel(): void
    {
        $channel = 'private-test-'.Str::uuid()->toString();

        $this->broadcaster->broadcast([$channel], 'test-event', ['payload' => 'private']);

        $this->assertTrue(true);
    }

    public function testBroadcastThrowsWhenCentrifugoRejectsRequest(): void
    {
        $config = $this->app['config']->get('broadcasting.connections.centrifugo');
        $config['api_key'] = 'invalid-api-key';

        $broadcaster = new CentrifugoBroadcaster(new Centrifugo($config, new Client()));

        $this->expectException(BroadcastException::class);

        $broadcaster->broadcast(['test-channel'], 'test-event', ['payload' => 'error']);
    }

    public function testAuthForPublicChannelReturnsConnectionToken(): void
    {
        $channel = 'auth-public-'.Str::uuid()->toString();

        $this->broadcaster->channel($channel, static fn ($user): bool => true);

        $request = Request::create('/broadcasting/auth', 'POST', [
            'client' => 'public-client-id',
            'channels' => $channel,
        ]);
        $request->setUserResolver(static fn ($guard = null): object => (object) ['id' => 1]);

        $response = $this->broadcaster->auth($request);
        $payload = json_decode($response->getContent(), true);
        $tokenPayload = $this->decodeJwtPayload($payload[$channel]['sign']);

        $this->assertSame('public-client-id', $tokenPayload['sub']);
        $this->assertSame([], $payload[$channel]['info']);
    }

    public function testAuthForPrivateChannelReturnsChannelToken(): void
    {
        $channel = 'private-auth-'.Str::uuid()->toString();
        $requestChannel = '$'.$channel;

        $this->broadcaster->channel($channel, static fn ($user): bool => true);

        $request = Request::create('/broadcasting/auth', 'POST', [
            'client' => 'private-client-id',
            'channels' => [$requestChannel],
        ]);
        $request->setUserResolver(static fn ($guard = null): object => (object) ['id' => 1]);

        $response = $this->broadcaster->auth($request);
        $payload = json_decode($response->getContent(), true);
        $channelPayload = $payload['channels'][0];
        $tokenPayload = $this->decodeJwtPayload($channelPayload['token']);

        $this->assertSame($requestChannel, $channelPayload['channel']);
        $this->assertSame($requestChannel, $tokenPayload['channel']);
        $this->assertSame('private-client-id', $tokenPayload['sub']);
        $this->assertArrayHasKey('result', $channelPayload['info']);
    }

    public function testAuthReturnsForbiddenWhenChannelAuthorizationFails(): void
    {
        $channel = 'auth-denied-'.Str::uuid()->toString();

        $this->broadcaster->channel($channel, static fn ($user): bool => false);

        $request = Request::create('/broadcasting/auth', 'POST', [
            'client' => 'forbidden-client-id',
            'channels' => [$channel],
        ]);
        $request->setUserResolver(static fn ($guard = null): object => (object) ['id' => 1]);

        $response = $this->broadcaster->auth($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(403, $payload[$channel]['status']);
    }

    public function testAuthThrowsUnauthorizedWhenRequestHasNoUser(): void
    {
        $request = Request::create('/broadcasting/auth', 'POST', [
            'client' => 'guest-client-id',
            'channels' => ['guest-channel'],
        ]);

        $this->expectException(HttpException::class);

        try {
            $this->broadcaster->auth($request);
        } catch (HttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function testValidAuthenticationResponseReturnsOriginalPayload(): void
    {
        $request = Request::create('/broadcasting/auth', 'POST');
        $result = ['status' => 'ok'];

        $this->assertSame($result, $this->broadcaster->validAuthenticationResponse($request, $result));
    }

    private function decodeJwtPayload(string $token): array
    {
        [, $payload] = explode('.', $token);

        return json_decode($this->decodeBase64Url($payload), true);
    }

    private function decodeBase64Url(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;

        return base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/')) ?: '';
    }
}
