<?php

namespace denis660\Centrifugo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'centrifuge:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centrifuge:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Centrifuge dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->addEnvironmentVariables();
        $this->updateBroadcastingConfiguration();
        $this->enableBroadcasting();
        $this->updateBroadcastingDriver();

        $this->components->info('Centrifuge Laravel  installed successfully.');
    }

    /**
     * Add the Centrifuge-laravel variables to the environment file.
     */
    protected function addEnvironmentVariables(): void
    {
        if (File::missing($env = app()->environmentFile())) {
            return;
        }
        $contents = File::get($env);

        $token_hmac_secret_key=Str::uuid()->toString();
        $api_key=Str::uuid()->toString();


        $variables = Arr::where([
            'CENTRIFUGO_TOKEN_HMAC_SECRET_KEY' => "CENTRIFUGO_TOKEN_HMAC_SECRET_KEY={$token_hmac_secret_key}",
            'CENTRIFUGO_API_KEY' => "CENTRIFUGO_API_KEY={$api_key}",
            'CENTRIFUGO_URL' => 'CENTRIFUGO_URL="http://localhost:8000"'
        ], function ($value, $key) use ($contents) {
            return ! Str::contains($contents, PHP_EOL.$key);
        });

        $variables = trim(implode(PHP_EOL, $variables));

        if ($variables === '') {
            return;
        }

        File::append(
            $env,
            Str::endsWith($contents, PHP_EOL) ? PHP_EOL.$variables.PHP_EOL : PHP_EOL.PHP_EOL.$variables.PHP_EOL,
        );
    }

    /**
     * Update the broadcasting.php configuration file.
     */
    protected function updateBroadcastingConfiguration(): void
    {
        if ($this->laravel->config->has('broadcasting.connections.centrifugo')) {
            return;
        }


        File::replaceInFile(
            "'connections' => [\n",
            <<<'CONFIG'
            'connections' => [

                    'centrifugo' => [
                        'driver' => 'centrifugo',
                        'token_hmac_secret_key' => env('CENTRIFUGO_TOKEN_HMAC_SECRET_KEY'),
                        'api_key' => env('CENTRIFUGO_API_KEY'),
                        'url' => env('CENTRIFUGO_URL', 'http://localhost:8000'), // centrifugo api url
                        'verify'=>env('CENTRIFUGO_VERIFY', false), // Verify host ssl if centrifugo uses this
                        'ssl_key'=>env('CENTRIFUGO_SSL_KEY', null)  // Self-Signed SSl Key for Host (require verify=true)

                    ],

            CONFIG,
            app()->configPath('broadcasting.php')
        );
    }

    /**
     * Enable Laravel's broadcasting functionality.
     */
    protected function enableBroadcasting(): void
    {
        $this->enableBroadcastServiceProvider();

        if (File::exists(base_path('routes/channels.php'))) {
            return;
        }

        $enable = confirm('Would you like to enable event broadcasting?', default: true);

        if (! $enable) {
            return;
        }

        if ($this->getApplication()->has('install:broadcasting')) {
            $this->call('install:broadcasting', ['--no-interaction' => true]);
        }
    }

    /**
     * Uncomment the "BroadcastServiceProvider" in the application configuration.
     */
    protected function enableBroadcastServiceProvider(): void
    {
        $config = File::get(app()->configPath('app.php'));

        if (Str::contains($config, '// App\Providers\BroadcastServiceProvider::class')) {
            File::replaceInFile(
                '// App\Providers\BroadcastServiceProvider::class',
                'App\Providers\BroadcastServiceProvider::class',
                app()->configPath('app.php'),
            );
        }
    }

    /**
     * Update the configured broadcasting driver.
     */
    protected function updateBroadcastingDriver(): void
    {
        if (File::missing($env = app()->environmentFile())) {
            return;
        }

        File::put(
            $env,
            Str::of(File::get($env))->replaceMatches('/(BROADCAST_(?:DRIVER|CONNECTION))=\w*/', function (array $matches) {
                return $matches[1].'=centrifugo';
            })
        );
    }
}
