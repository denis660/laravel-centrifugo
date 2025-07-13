<?php
declare(strict_types=1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Centrifugo;
use denis660\Centrifugo\CentrifugoBroadcaster;
use denis660\Centrifugo\CentrifugoServiceProvider;
use denis660\Centrifugo\Test\TestCase;
use Illuminate\Broadcasting\BroadcastManager;

class CentrifugoServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [CentrifugoServiceProvider::class];
    }

    public function testBinding()
    {
        $this->app['config']->set('broadcasting.connections.centrifugo', [
            'driver' => 'centrifugo',
            'token_hmac_secret_key' => 'test-secret',
            'api_key' => 'test-api-key',
        ]);

        $centrifugo = $this->app->make('centrifugo');
        $this->assertInstanceOf(Centrifugo::class, $centrifugo);
    }

    public function testBroadcaster()
    {
        $this->app['config']->set('broadcasting.connections.centrifugo', [
            'driver' => 'centrifugo',
            'token_hmac_secret_key' => 'test-secret',
            'api_key' => 'test-api-key',
        ]);

        $manager = $this->app->make(BroadcastManager::class);
        $broadcaster = $manager->connection('centrifugo');
        $this->assertInstanceOf(CentrifugoBroadcaster::class, $broadcaster);
    }
} 