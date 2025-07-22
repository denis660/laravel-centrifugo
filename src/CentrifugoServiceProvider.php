<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use denis660\Centrifugo\Commands\InstallCommand;
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
        if ($this->app->runningInConsole()) {
            $this->commands(InstallCommand::class);
        }

        $broadcastManager->extend('centrifugo', function ($app) {
            return new CentrifugoBroadcaster($app->make('centrifugo'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('centrifugo', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.centrifugo');
            $http = new HttpClient();

            return new Centrifugo($config, $http);
        });

        $this->app->alias('centrifugo', 'denis660\Centrifugo\Centrifugo');
        $this->app->alias('centrifugo', 'denis660\Centrifugo\Contracts\Centrifugo');
    }
}
