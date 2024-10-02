<?php

namespace denis660\Centrifugo\Test;

use denis660\Centrifugo\{Centrifugo, CentrifugoServiceProvider};

/**
 * @internal
 *
 * @coversNothing
 */
class TestCase extends \Orchestra\Testbench\TestCase
{
    protected Centrifugo $centrifuge;

    public function setUp(): void
    {
        parent::setUp();
        $this->centrifuge = $this->app->make('centrifugo');
    }

    protected function getPackageProviders($app): array
    {
        return [
            CentrifugoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        
        $app['config']->set('broadcasting.default', 'centrifugo');
        $app['config']->set('broadcasting.connections.centrifugo', [
            'driver' => 'centrifugo',
            'token_hmac_secret_key' => '', //0cac68ac-5965-46bf-8c44-ed28a03ab8ba
            'api_key' => '', //ae3362d2-5862-4261-9b3b-6b231f8863c8
            'url' => 'http://localhost:8000',
        ]);
    }
}
