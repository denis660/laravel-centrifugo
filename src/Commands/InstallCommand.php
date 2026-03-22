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
        $this->ensureBroadcastingIsInstalled();
        $this->updateBroadcastingConfiguration();
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

        $token_hmac_secret_key = Str::uuid()->toString();
        $api_key = Str::uuid()->toString();


        $variables = Arr::where([
            'CENTRIFUGO_TOKEN_HMAC_SECRET_KEY' => "CENTRIFUGO_TOKEN_HMAC_SECRET_KEY={$token_hmac_secret_key}",
            'CENTRIFUGO_API_KEY' => "CENTRIFUGO_API_KEY={$api_key}",
            'CENTRIFUGO_URL' => 'CENTRIFUGO_URL="http://localhost:8000"',
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
     * Ensure Laravel broadcasting is installed for the current app structure.
     */
    protected function ensureBroadcastingIsInstalled(): void
    {
        $this->enableBroadcastServiceProvider();

        $broadcastingConfig = app()->configPath('broadcasting.php');

        if (File::exists($broadcastingConfig) && File::exists(base_path('routes/channels.php'))) {
            return;
        }

        if (! $this->getApplication()->has('install:broadcasting')) {
            return;
        }

        $enable = $this->confirm('Would you like to enable event broadcasting?', true);

        if (! $enable) {
            return;
        }

        $this->call('install:broadcasting', $this->broadcastingInstallOptions());
    }

    /**
     * Update the broadcasting.php configuration file.
     */
    protected function updateBroadcastingConfiguration(): void
    {
        $broadcastingConfig = app()->configPath('broadcasting.php');

        if (File::missing($broadcastingConfig)) {
            $this->components->warn('Skipping Centrifugo broadcasting configuration because config/broadcasting.php was not found.');

            return;
        }

        $contents = File::get($broadcastingConfig);
        $updated = $this->injectCentrifugoConnection($contents);

        if ($updated === $contents) {
            return;
        }

        File::put($broadcastingConfig, $updated);
    }

    /**
     * Uncomment the "BroadcastServiceProvider" in the application configuration.
     */
    protected function enableBroadcastServiceProvider(): void
    {
        $appConfig = app()->configPath('app.php');

        if (File::missing($appConfig)) {
            return;
        }

        $config = File::get($appConfig);

        if (Str::contains($config, '// App\Providers\BroadcastServiceProvider::class')) {
            File::replaceInFile(
                '// App\Providers\BroadcastServiceProvider::class',
                'App\Providers\BroadcastServiceProvider::class',
                $appConfig,
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

        $contents = File::get($env);
        $contents = $this->upsertEnvironmentVariable($contents, 'BROADCAST_DRIVER', 'centrifugo');
        $contents = $this->upsertEnvironmentVariable($contents, 'BROADCAST_CONNECTION', 'centrifugo');

        File::put($env, $contents);
    }

    /**
     * Inject the Centrifugo connection into the broadcasting config contents.
     */
    protected function injectCentrifugoConnection(string $contents): string
    {
        if (Str::contains($contents, "'centrifugo' => [")) {
            return $contents;
        }

        $connection = <<<'CONFIG'

        'centrifugo' => [
            'driver' => 'centrifugo',
            'token_hmac_secret_key' => env('CENTRIFUGO_TOKEN_HMAC_SECRET_KEY'),
            'api_key' => env('CENTRIFUGO_API_KEY'),
            'url' => env('CENTRIFUGO_URL', 'http://localhost:8000'), // centrifugo api url
            'verify' => env('CENTRIFUGO_VERIFY', false), // Verify host ssl if centrifugo uses this
            'ssl_key' => env('CENTRIFUGO_SSL_KEY', null), // Self-Signed SSL Key for Host (require verify=true)
        ],
CONFIG;

        $updated = preg_replace(
            "/('connections'\s*=>\s*\[\s*\R)/",
            "$1{$connection}\n\n",
            $contents,
            1,
            $count
        );

        return $count === 1 && is_string($updated) ? $updated : $contents;
    }

    /**
     * Replace an existing env var or append it if missing.
     */
    protected function upsertEnvironmentVariable(string $contents, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}={$value}";

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $replacement, $contents);
        }

        return Str::endsWith($contents, PHP_EOL)
            ? $contents.$replacement.PHP_EOL
            : $contents.PHP_EOL.$replacement.PHP_EOL;
    }

    /**
     * Determine the options used when installing Laravel broadcasting scaffolding.
     */
    protected function broadcastingInstallOptions(): array
    {
        return [
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
            '--no-interaction' => true,
        ];
    }
}
