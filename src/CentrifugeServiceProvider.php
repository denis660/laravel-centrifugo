<?php

declare(strict_types=1);

namespace denis660\Centrifuge;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

class CentrifugeServiceProvider extends ServiceProvider
{
    /**
     * Add centrifugo broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifuge', function ($app) {
            return new CentrifugeBroadcaster($app->make('centrifuge'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('centrifuge', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.centrifuge');
            $http = new HttpClient();

            return new Centrifuge($config, $http);
        });

        $this->app->alias('centrifuge', 'denis660\Centrifuge\Centrifuge');
        $this->app->alias('centrifuge', 'denis660\Centrifuge\Contracts\Centrifuge');
    }
}
