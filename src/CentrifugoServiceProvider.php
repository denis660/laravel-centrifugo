<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

class CentrifugoServiceProvider extends ServiceProvider
{
    /**
     * Add centrifugo broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifuge', function ($app) {
            return new CentrifugoBroadcaster($app->make('centrifuge'));
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

            return new Centrifugo($config, $http);
        });

        $this->app->alias('centrifuge', 'denis660\Centrifuge\Centrifugo');
        $this->app->alias('centrifuge', 'denis660\Centrifuge\Contracts\Centrifuge');
    }
}
