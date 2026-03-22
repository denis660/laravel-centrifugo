<?php

declare(strict_types=1);

namespace denis660\Centrifugo;

use denis660\Centrifugo\Commands\InstallCommand;
use denis660\Centrifugo\Contracts\CentrifugoInterface;
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
            return new CentrifugoBroadcaster($app->make(CentrifugoInterface::class));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Centrifugo::class, function ($app) {
            $config = (array) $app->make('config')->get('broadcasting.connections.centrifugo', []);
            $http = new HttpClient();

            return new Centrifugo($config, $http);
        });

        $this->app->alias(Centrifugo::class, 'centrifugo');
        $this->app->alias(Centrifugo::class, CentrifugoInterface::class);
    }
}
