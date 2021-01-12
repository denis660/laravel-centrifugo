<?php

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
        $broadcastManager->extend('centrifugo', function ($app) {
            return new CentrifugeBroadcaster($app->make('centrifugo'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('centrifugo', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.centrifugo');
            $http = new HttpClient();

            return new Centrifuge($config, $http);
        });

        $this->app->alias('centrifugo', 'denis660\Centrifuge\Centrifuge');
        $this->app->alias('centrifugo', 'denis660\Centrifuge\Contracts\Centrifugo');
    }
}
