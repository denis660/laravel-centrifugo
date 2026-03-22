<?php
declare(strict_types = 1);

namespace denis660\Centrifugo\Test\Unit;

use Carbon\CarbonImmutable;
use denis660\Centrifugo\Centrifugo;
use denis660\Centrifugo\Test\TestCase;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * @internal
 *
 * @coversNothing
 */


class CentrifugoTest extends TestCase
{



    public function testGenerateToken(): void
    {



        $info = [
            'first_name' => "Tristian",
            'last_name' => "Ruecker",
        ];
        $clientId = "f2527b6a-6705-45b7-a1d9-d0029943dc20";

        $clientToken1 = $this->centrifuge->generateConnectionToken(userId:$clientId);

        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmMjUyN2I2YS02NzA1LTQ1YjctYTFkOS1kMDAyOTk0M2RjMjAifQ.csRuDJhBalF3J3v6JnsNZmOXLx6nbNMi3zcCieJJqng',
            $clientToken1
        );

        $clientToken2 = $this->centrifuge->generatePrivateChannelToken(userId:$clientId, channel:'test', info:$info);

        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjaGFubmVsIjoidGVzdCIsInN1YiI6ImYyNTI3YjZhLTY3MDUtNDViNy1hMWQ5LWQwMDI5OTQzZGMyMCIsImluZm8iOnsiZmlyc3RfbmFtZSI6IlRyaXN0aWFuIiwibGFzdF9uYW1lIjoiUnVlY2tlciJ9fQ.8wNO3gQdg6Knk9J7mPZquV8aAsDtqI2a4A5tK5Y9IR8',
            $clientToken2
        );
    }

    public function testGenerateTokenWithOptionalPayloadFields(): void
    {
        $frozenTime = CarbonImmutable::parse('2026-03-22 12:00:00', 'UTC');

        $this->travelTo($frozenTime);

        $token = $this->centrifuge->generateConnectionToken(
            userId: 'client-id',
            exp: 60,
            info: ['role' => 'admin'],
            channels: ['alpha', 'beta']
        );

        $payload = $this->decodeJwtPayload($token);

        $this->assertSame('client-id', $payload['sub']);
        $this->assertSame(['role' => 'admin'], $payload['info']);
        $this->assertSame(['alpha', 'beta'], $payload['channels']);
        $this->assertSame($frozenTime->addSeconds(60)->timestamp, $payload['exp']);
        $this->assertSame($frozenTime->timestamp, $payload['iat']);
    }

    public function testGeneratePrivateChannelTokenWithExpiration(): void
    {
        $frozenTime = CarbonImmutable::parse('2026-03-22 12:30:00', 'UTC');

        $this->travelTo($frozenTime);

        $token = $this->centrifuge->generatePrivateChannelToken(
            userId: 'private-client-id',
            channel: '$private-channel',
            exp: 120,
            info: ['scope' => 'private']
        );

        $payload = $this->decodeJwtPayload($token);

        $this->assertSame('$private-channel', $payload['channel']);
        $this->assertSame('private-client-id', $payload['sub']);
        $this->assertSame(['scope' => 'private'], $payload['info']);
        $this->assertSame($frozenTime->addSeconds(120)->timestamp, $payload['exp']);
        $this->assertSame($frozenTime->timestamp, $payload['iat']);
    }

    public function testCentrifugoApiPublish(): void
    {
        $publish = $this->centrifuge->publish('test-test', ['event' => 'test-event']);
        $this->assertArrayHasKey('result', $publish);
        $this->assertIsArray($publish['result']);
    }

    public function testCentrifugoApiBroadcast(): void
    {
        $broadcast = $this->centrifuge->broadcast(['test-channel-1', 'test-channel-2'], ['event' => 'test-event']);
        $this->assertCount(2, $broadcast['result']['responses']);
        $this->assertArrayHasKey('result', $broadcast['result']['responses'][0]);
        $this->assertArrayHasKey('result', $broadcast['result']['responses'][1]);
    }


    public function testCentrifugoApiPresence(): void
    {
        $presence = $this->centrifuge->presence('test-channel');
        $this->assertArrayHasKey('presence', $presence['result']);
        $this->assertEmpty($presence['result']['presence']);
    }

    public function testCentrifugoApiHistory(): void
    {
        $history = $this->centrifuge->history('test-channel');
        $this->assertArrayHasKey('publications', $history['result']);
        $this->assertEmpty($history['result']['publications']);
    }

    public function testCentrifugoApiHistorySupportsSinceParameter(): void
    {
        $channel = 'history-since-'.Str::uuid()->toString();

        $this->centrifuge->historyRemove($channel);
        $this->centrifuge->publish($channel, ['event' => 'history-event']);

        $history = $this->centrifuge->history($channel);

        $this->assertArrayHasKey('offset', $history['result']);
        $this->assertArrayHasKey('epoch', $history['result']);

        $withSince = $this->centrifuge->history(
            $channel,
            1,
            [
                'offset' => $history['result']['offset'],
                'epoch' => $history['result']['epoch'],
            ],
            true
        );

        $this->assertArrayHasKey('publications', $withSince['result']);
    }

    public function testCentrifugoApiChannels(): void
    {
        $channels = $this->centrifuge->channels();
        $this->assertArrayHasKey('channels', $channels['result']);
    }

    public function testCentrifugoApiHistoryRemove(): void
    {
        $historyRemove = $this->centrifuge->historyRemove('test-channel');
        $this->assertEquals([], $historyRemove['result']);
    }

    public function testCentrifugoApiDisconnect(): void
    {
        $disconnect = $this->centrifuge->disconnect('test-user-id');
        $this->assertEquals([], $disconnect['result']);
    }

    public function testCentrifugoApiInfo(): void
    {
        $info = $this->centrifuge->info();

        $this->assertArrayNotHasKey('error', $info);
        $this->assertArrayHasKey('result', $info);
        $this->assertArrayHasKey('nodes', $info['result']);
    }

    public function testInfoSendsEmptyJsonObject(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['result' => ['nodes' => []]], JSON_THROW_ON_ERROR)
            ),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));

        $centrifugo = new Centrifugo(
            [
                'driver' => 'centrifugo',
                'token_hmac_secret_key' => 'secret',
                'api_key' => 'api-key',
                'url' => 'http://localhost:8000',
            ],
            new Client(['handler' => $handlerStack])
        );

        $info = $centrifugo->info();

        $this->assertSame(['result' => ['nodes' => []]], $info);
        $this->assertCount(1, $history);
        $this->assertSame('{}', (string) $history[0]['request']->getBody());
        $this->assertSame('/api/info', $history[0]['request']->getUri()->getPath());
    }

    public function testCentrifugoApiUnsubscribe(): void
    {
        $unsubscribe = $this->centrifuge->unsubscribe('test-channel', '1');
        $this->assertEquals([], $unsubscribe['result']);
    }

    public function testCentrifugoApiSubscribe(): void
    {
        $subscribe = $this->centrifuge->subscribe('test-channel', '1');
        $this->assertEquals([], $subscribe['result']);
    }

    // 108 error if centrifugo config "presence" dont set
    // for correct response you need set centrifugo config "presence": true
    public function testCentrifugoApiStats(): void
    {
        $stats = $this->centrifuge->presenceStats('test-channel');

        $this->assertEquals([
            'result' => [
                'num_clients' => 0,
                'num_users' => 0,
            ],
        ], $stats);

    }

    public function testSendReturnsErrorPayloadWhenConnectionFails(): void
    {
        $badCentrifugo = new Centrifugo(
            [
                'driver' => 'centrifugo',
                'token_hmac_secret_key' => '',
                'api_key' => '',
                'url' => 'http://localhost:3999',
            ],
            new Client()
        );

        $result = $badCentrifugo->publish('test-channel', ['event' => 'test-event']);

        $this->assertSame('publish', $result['method']);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame([
            'channel' => 'test-channel',
            'data' => ['event' => 'test-event'],
            'skip_history' => false,
        ], $result['body']);
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
