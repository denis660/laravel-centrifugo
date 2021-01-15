<?php

declare(strict_types=1);

namespace denis660\Centrifuge;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

class CentrifugeServiceProvider extends ServiceProvider
{
    /**
     * Add Centrifuge broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('Centrifuge', function ($app) {
            return new CentrifugeBroadcaster($app->make('Centrifuge'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Centrifuge', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.Centrifuge');
            $http = new HttpClient();

            return new Centrifuge($config, $http);
        });

        $this->app->alias('Centrifuge', 'denis660\Centrifuge\Centrifuge');
        $this->app->alias('Centrifuge', 'denis660\Centrifuge\Contracts\Centrifuge');
    }
}
