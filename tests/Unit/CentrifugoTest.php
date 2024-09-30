<?php

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

        $timestamp = 1727716803;

        $info = [
            'first_name' => "Tristian",
            'last_name' => "Ruecker",
        ];
        $clientId = "f2527b6a-6705-45b7-a1d9-d0029943dc20";

        $clientToken1 = $this->centrifuge->generateConnectionToken($clientId, $timestamp);


        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJmMjUyN2I2YS02NzA1LTQ1YjctYTFkOS1kMDAyOTk0M2RjMjAiLCJleHAiOjE3Mjc3MTY4MDN9.om2AdKJLNYbZ7bfeIS9tw0AJqs1RIp5irUGNfPiqHqk',
            $clientToken1
        );

        $clientToken2 = $this->centrifuge->generatePrivateChannelToken($clientId, 'test', $timestamp, $info);
        $this->assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjaGFubmVsIjoidGVzdCIsImNsaWVudCI6ImYyNTI3YjZhLTY3MDUtNDViNy1hMWQ5LWQwMDI5OTQzZGMyMCIsImluZm8iOnsiZmlyc3RfbmFtZSI6IlRyaXN0aWFuIiwibGFzdF9uYW1lIjoiUnVlY2tlciJ9LCJleHAiOjE3Mjc3MTY4MDN9.UtzQDjU1RDXKizcBlvF5VMR45_crs6AU4tcO-EfPnjM',
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
