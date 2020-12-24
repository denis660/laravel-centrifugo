<?php

namespace denis660\Laracent;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

class LaracentServiceProvider extends ServiceProvider
{
    /**
     * Add centrifugo broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifugo', function ($app) {
            return new LaracentBroadcaster($app->make('centrifugo'));
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

            return new Laracent($config, $http);
        });

        $this->app->alias('centrifugo', 'denis660\Laracent\Laracent');
        $this->app->alias('centrifugo', 'denis660\Laracent\Contracts\Centrifugo');
    }
}
