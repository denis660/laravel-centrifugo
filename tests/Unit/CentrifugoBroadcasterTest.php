<?php
declare(strict_types=1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Centrifugo;
use denis660\Centrifugo\CentrifugoBroadcaster;
use denis660\Centrifugo\Test\TestCase;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Mockery as m;
use stdClass;

class CentrifugoBroadcasterTest extends TestCase
{
    /**
     * @var \denis660\Centrifugo\Centrifugo|\Mockery\MockInterface
     */
    public $centrifugo;

    /**
     * @var \denis660\Centrifugo\CentrifugoBroadcaster|\Mockery\MockInterface
     */
    public $broadcaster;

    public function setUp(): void
    {
        parent::setUp();
        $this->centrifugo = m::mock(Centrifugo::class);
        $this->broadcaster = new CentrifugoBroadcaster($this->centrifugo);
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function testBroadcast()
    {
        $this->centrifugo->shouldReceive('broadcast')->once()->with(['test-channel'], ['event' => 'test-event'])->andReturn([]);
        $this->broadcaster->broadcast(['test-channel'], 'test-event', ['event' => 'test-event']);
        self::assertTrue(true);
    }

    public function testBroadcastWithPrivateChannel()
    {
        $this->centrifugo->shouldReceive('broadcast')->once()->with(['$test-channel'], ['event' => 'test-event'])->andReturn([]);
        $this->broadcaster->broadcast(['private-test-channel'], 'test-event', ['event' => 'test-event']);
        self::assertTrue(true);
    }

    public function testBroadcastException()
    {
        $this->expectException(BroadcastException::class);
        $this->centrifugo->shouldReceive('broadcast')->once()->with(['test-channel'], ['event' => 'test-event'])->andReturn(['error' => 'test-error']);
        $this->broadcaster->broadcast(['test-channel'], 'test-event', ['event' => 'test-event']);
    }

    public function testAuthForPublicChannel()
    {
        $this->centrifugo->shouldReceive('generateConnectionToken')->once()->andReturn('test-token');

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn(new stdClass());
        $request->shouldReceive('get')->with('client', '')->andReturn('test-client-id');
        $request->shouldReceive('get')->with('channels', [])->andReturn(['test-channel']);

        $broadcaster = m::mock(CentrifugoBroadcaster::class.'[verifyUserCanAccessChannel]', [$this->centrifugo])->shouldAllowMockingProtectedMethods();
        $broadcaster->shouldReceive('verifyUserCanAccessChannel')->andReturn(true);

        $response = $broadcaster->auth($request);
        $this->assertEquals(['test-channel' => ['sign' => 'test-token', 'info' => []]], json_decode($response->getContent(), true));
    }

    public function testAuthForPrivateChannel()
    {
        $this->centrifugo->shouldReceive('generatePrivateChannelToken')->once()->andReturn('private-test-token');

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn(new stdClass());
        $request->shouldReceive('get')->with('client', '')->andReturn('test-client-id');
        $request->shouldReceive('get')->with('channels', [])->andReturn(['$private-channel']);

        $broadcaster = m::mock(CentrifugoBroadcaster::class.'[verifyUserCanAccessChannel]', [$this->centrifugo])->shouldAllowMockingProtectedMethods();
        $broadcaster->shouldReceive('verifyUserCanAccessChannel')->andReturn(true);

        $response = $broadcaster->auth($request);
        $expected = [
            'channels' => [
                [
                    'channel' => '$private-channel',
                    'token'   => 'private-test-token',
                    'info'    => [],
                ],
            ],
        ];
        $this->assertEquals($expected, json_decode($response->getContent(), true));
    }

    public function testAuthAccessDenied()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn(new stdClass());
        $request->shouldReceive('get')->with('client', '')->andReturn('test-client-id');
        $request->shouldReceive('get')->with('channels', [])->andReturn(['test-channel']);

        $broadcaster = m::mock(CentrifugoBroadcaster::class.'[verifyUserCanAccessChannel]', [$this->centrifugo])->shouldAllowMockingProtectedMethods();
        $broadcaster->shouldReceive('verifyUserCanAccessChannel')->andReturn(false);

        $response = $broadcaster->auth($request);
        $this->assertEquals(['test-channel' => ['status' => 403]], json_decode($response->getContent(), true));
    }
} 