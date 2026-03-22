<?php

declare(strict_types=1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Commands\InstallCommand;
use denis660\Centrifugo\Test\Support\TemporaryLaravelAppTestCase;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use ReflectionMethod;
use Symfony\Component\Console\Application as SymfonyApplication;

class RecordingBroadcastingInstallCommand extends Command
{
    protected $signature = 'install:broadcasting {--without-node} {--without-reverb} {--reverb}';

    protected $description = 'Record broadcasting installation options';

    public function handle(): int
    {
        $configPath = app()->configPath('broadcasting.php');

        if (! is_dir(dirname($configPath))) {
            mkdir(dirname($configPath), 0777, true);
        }

        file_put_contents($configPath, <<<'PHP'
<?php

return [
    'connections' => [
        'log' => [
            'driver' => 'log',
        ],
    ],
];
PHP);

        $routesPath = base_path('routes/channels.php');

        if (! is_dir(dirname($routesPath))) {
            mkdir(dirname($routesPath), 0777, true);
        }

        file_put_contents($routesPath, "<?php\n");

        $recordPath = base_path('bootstrap/cache/install-broadcasting-options.json');

        if (! is_dir(dirname($recordPath))) {
            mkdir(dirname($recordPath), 0777, true);
        }

        file_put_contents($recordPath, json_encode([
            'without-node' => $this->option('without-node'),
            'without-reverb' => $this->option('without-reverb'),
            'reverb' => $this->option('reverb'),
        ], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}

class InstallCommandTest extends TemporaryLaravelAppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->writeAppFile('config/app.php', <<<'PHP'
<?php

return [
    'providers' => [
        // App\Providers\BroadcastServiceProvider::class,
    ],
];
PHP);
    }

    public function testHandleRunsFullInstallFlowAgainstRealFilesystem(): void
    {
        $this->registerRecordingBroadcastingInstallCommand();

        $this->deleteAppFile('config/broadcasting.php');
        $this->deleteAppFile('routes/channels.php');
        $this->writeAppFile('.env', 'APP_NAME=Laravel');

        $this->artisan('centrifuge:install')
            ->expectsConfirmation('Would you like to enable event broadcasting?', 'yes')
            ->assertExitCode(0);

        $env = $this->readAppFile('.env');
        $broadcasting = $this->readAppFile('config/broadcasting.php');
        $appConfig = $this->readAppFile('config/app.php');
        $options = json_decode($this->readAppFile('bootstrap/cache/install-broadcasting-options.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=', $env);
        $this->assertStringContainsString('CENTRIFUGO_API_KEY=', $env);
        $this->assertStringContainsString('CENTRIFUGO_URL="http://localhost:8000"', $env);
        $this->assertStringContainsString('BROADCAST_DRIVER=centrifugo', $env);
        $this->assertStringContainsString('BROADCAST_CONNECTION=centrifugo', $env);
        $this->assertStringContainsString("'centrifugo' => [", $broadcasting);
        $this->assertStringContainsString("'driver' => 'centrifugo'", $broadcasting);
        $this->assertSame("<?php\n", $this->readAppFile('routes/channels.php'));
        $this->assertStringNotContainsString('// App\Providers\BroadcastServiceProvider::class', $appConfig);
        $this->assertStringContainsString('App\Providers\BroadcastServiceProvider::class', $appConfig);
        $this->assertSame([
            'without-node' => true,
            'without-reverb' => true,
            'reverb' => true,
        ], $options);
    }

    public function testHandleSkipsInstallerWhenUserDeclinesAndWarnsAboutMissingConfig(): void
    {
        $this->registerRecordingBroadcastingInstallCommand();

        $this->deleteAppFile('config/broadcasting.php');
        $this->deleteAppFile('routes/channels.php');
        $this->writeAppFile('.env', "APP_NAME=Laravel\n");

        $this->artisan('centrifuge:install')
            ->expectsConfirmation('Would you like to enable event broadcasting?', 'no')
            ->expectsOutputToContain('Skipping Centrifugo broadcasting configuration because config/broadcasting.php was not found.')
            ->assertExitCode(0);

        $env = $this->readAppFile('.env');

        $this->assertStringContainsString('CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=', $env);
        $this->assertStringContainsString('BROADCAST_CONNECTION=centrifugo', $env);
        $this->assertFileDoesNotExist($this->appFilePath('bootstrap/cache/install-broadcasting-options.json'));
        $this->assertFileDoesNotExist($this->appFilePath('routes/channels.php'));
        $this->assertFileDoesNotExist($this->appFilePath('config/broadcasting.php'));
    }

    public function testAddEnvironmentVariablesReturnsEarlyForMissingFileAndExistingValues(): void
    {
        $command = $this->installCommand();

        $this->deleteAppFile('.env');

        $this->invokeProtected($command, 'addEnvironmentVariables');

        $this->assertFileDoesNotExist($this->appFilePath('.env'));

        $contents = implode("\n", [
            'APP_NAME=Laravel',
            'CENTRIFUGO_TOKEN_HMAC_SECRET_KEY=secret',
            'CENTRIFUGO_API_KEY=api-key',
            'CENTRIFUGO_URL="http://localhost:8000"',
        ])."\n";

        $this->writeAppFile('.env', $contents);

        $this->invokeProtected($command, 'addEnvironmentVariables');

        $this->assertSame($contents, $this->readAppFile('.env'));
    }

    public function testEnsureBroadcastingIsInstalledReturnsWhenScaffoldingExistsOrInstallerIsUnavailable(): void
    {
        $command = $this->installCommand();

        $this->writeAppFile('routes/channels.php', "<?php\n");

        $this->invokeProtected($command, 'ensureBroadcastingIsInstalled');

        $this->assertSame("<?php\n", $this->readAppFile('routes/channels.php'));

        $this->deleteAppFile('routes/channels.php');

        $command->setApplication(new SymfonyApplication());

        $this->invokeProtected($command, 'ensureBroadcastingIsInstalled');

        $this->assertFileDoesNotExist($this->appFilePath('routes/channels.php'));
    }

    public function testUpdateBroadcastingConfigurationAndDriverHandleEarlyReturns(): void
    {
        $command = $this->installCommand();
        $existingConfig = <<<'PHP'
<?php

return [
    'connections' => [
        'centrifugo' => [
            'driver' => 'centrifugo',
        ],
    ],
];
PHP;

        $this->writeAppFile('config/broadcasting.php', $existingConfig);

        $this->invokeProtected($command, 'updateBroadcastingConfiguration');

        $this->assertSame($existingConfig, $this->readAppFile('config/broadcasting.php'));

        $this->deleteAppFile('.env');

        $this->invokeProtected($command, 'updateBroadcastingDriver');

        $this->assertFileDoesNotExist($this->appFilePath('.env'));
    }

    public function testEnableBroadcastServiceProviderReturnsEarlyWhenConfigIsMissing(): void
    {
        $command = $this->installCommand();

        $this->deleteAppFile('config/app.php');

        $this->invokeProtected($command, 'enableBroadcastServiceProvider');

        $this->assertFileDoesNotExist($this->appFilePath('config/app.php'));
    }

    public function testProtectedHelpersUseRealCommandDefinitionAndTransformContents(): void
    {
        $this->registerRecordingBroadcastingInstallCommand();

        $command = $this->installCommand();
        $broadcastingConfig = <<<'PHP'
<?php

return [
    'connections' => [
        'log' => [
            'driver' => 'log',
        ],
    ],
];
PHP;
        $existingConnection = <<<'PHP'
<?php

return [
    'connections' => [
        'centrifugo' => [
            'driver' => 'centrifugo',
        ],
    ],
];
PHP;

        $this->assertTrue($this->invokeProtected($command, 'hasBroadcastingInstallOption', 'without-node'));
        $this->assertSame([
            '--no-interaction' => true,
            '--without-node' => true,
            '--without-reverb' => true,
            '--reverb' => true,
        ], $this->invokeProtected($command, 'broadcastingInstallOptions'));

        $updatedConfig = $this->invokeProtected($command, 'injectCentrifugoConnection', $broadcastingConfig);

        $this->assertStringContainsString("'centrifugo' => [", $updatedConfig);
        $this->assertSame($existingConnection, $this->invokeProtected($command, 'injectCentrifugoConnection', $existingConnection));
        $this->assertSame(
            "APP_NAME=Laravel\nBROADCAST_DRIVER=centrifugo\n",
            $this->invokeProtected($command, 'upsertEnvironmentVariable', "APP_NAME=Laravel\nBROADCAST_DRIVER=log\n", 'BROADCAST_DRIVER', 'centrifugo')
        );
        $this->assertSame(
            "APP_NAME=Laravel\nBROADCAST_CONNECTION=centrifugo\n",
            $this->invokeProtected($command, 'upsertEnvironmentVariable', "APP_NAME=Laravel\n", 'BROADCAST_CONNECTION', 'centrifugo')
        );
    }

    private function installCommand(): InstallCommand
    {
        $kernel = $this->app->make(ConsoleKernel::class);
        $commands = $kernel->all();
        $command = $commands['centrifuge:install'];

        $this->assertInstanceOf(InstallCommand::class, $command);

        return $command;
    }

    private function invokeProtected(InstallCommand $command, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($command, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($command, ...$arguments);
    }

    private function registerRecordingBroadcastingInstallCommand(): void
    {
        $this->app->make(ConsoleKernel::class)->registerCommand(new RecordingBroadcastingInstallCommand());
    }
}
