<?php
declare(strict_types = 1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Centrifugo;
use denis660\Centrifugo\Test\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

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

    public function testCentrifugoApiPublish(): void
    {
        $publish = $this->centrifuge->publish('test-test', ['event' => 'test-event']);
        $this->assertArrayHasKey('result', $publish);
        $this->assertIsArray($publish['result']);
    }

    public function testCentrifugoApiBroadcast(): void
    {
        $broadcast = $this->centrifuge->broadcast(['test-channel-1', 'test-channel-2'], ['event' => 'test-event']);
        dd($broadcast);
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

    public function testCentrifugoApiChannels(): void
    {
        $channels = $this->centrifuge->channels();
        $this->assertArrayHasKey('channels', $channels['result']);
    }

    public function testCentrifugoApiUnsubscribe(): void
    {
        $unsubscribe = $this->centrifuge->unsubscribe('test-channel', '1');
        $this->assertEquals([], $unsubscribe['result']);
    }

    public function testCentrifugoApiSubscribe(): void
    {
        $subscribe = $this->centrifuge->unsubscribe('test-channel', '1');
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

    public function testTimeoutFunction(): void
    {
        $timeout = 3;
        $delta = 0.5;

        $badCentrifugo = new Centrifugo(
            [
                'driver' => 'centrifugo',
                'token_hmac_secret_key' => '',
                'api_key' => '',
                'api_path' => '',
                'url' => 'http://localhost:3999',
                'timeout' => $timeout,
                'tries' => 1,
            ],
            new Client()
        );

        $start = microtime(true);
        $this->expectException(ConnectException::class);

        try {
            $badCentrifugo->publish('test-channel', ['event' => 'test-event']);
        } catch (\Exception $e) {
            $end = microtime(true);
            $eval = $end - $start;
            $this->assertTrue($eval < $timeout + $delta);

            throw $e;
        }
    }

    public function testTriesFunction(): void
    {
        $timeout = 1;
        $tries = 3;
        $delta = 0.5;

        $badCentrifugo = new Centrifugo(
            [
                'driver' => 'centrifugo',
                'token_hmac_secret_key' => '',
                'api_key' => '',
                'api_path' => '',
                'url' => 'http://localhost:3999',
                'timeout' => $timeout,
                'tries' => $tries,
            ],
            new Client()
        );

        $start = microtime(true);

        $this->expectException(ConnectException::class);

        try {
            $badCentrifugo->publish('test-channel', ['event' => 'test-event']);
        } catch (\Exception $e) {
            $end = microtime(true);
            $eval = $end - $start;
            $this->assertTrue($eval < ($timeout + $delta) * $tries);

            throw $e;
        }
    }
}
