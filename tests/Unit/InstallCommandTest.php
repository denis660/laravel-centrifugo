<?php
declare(strict_types=1);

namespace denis660\Centrifugo\Test\Unit;

use denis660\Centrifugo\Commands\InstallCommand;
use PHPUnit\Framework\TestCase;

class InstallCommandTest extends TestCase
{
    private InstallCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new class extends InstallCommand
        {
            public function injectConnection(string $contents): string
            {
                return $this->injectCentrifugoConnection($contents);
            }

            public function upsertEnv(string $contents, string $key, string $value): string
            {
                return $this->upsertEnvironmentVariable($contents, $key, $value);
            }

            public function installOptions(): array
            {
                return $this->broadcastingInstallOptions();
            }
        };
    }

    public function testInjectCentrifugoConnectionIntoBroadcastingConfig(): void
    {
        $contents = <<<'PHP'
<?php

return [
    'connections' => [
        'log' => [
            'driver' => 'log',
        ],
    ],
];
PHP;

        $updated = $this->command->injectConnection($contents);

        $this->assertStringContainsString("'centrifugo' => [", $updated);
        $this->assertStringContainsString("'driver' => 'centrifugo'", $updated);
        $this->assertStringContainsString("'log' => [", $updated);
    }

    public function testInjectCentrifugoConnectionIsIdempotent(): void
    {
        $contents = <<<'PHP'
<?php

return [
    'connections' => [
        'centrifugo' => [
            'driver' => 'centrifugo',
        ],
    ],
];
PHP;

        $this->assertSame($contents, $this->command->injectConnection($contents));
    }

    public function testUpsertEnvironmentVariableReplacesExistingValue(): void
    {
        $contents = "APP_NAME=Laravel\nBROADCAST_DRIVER=log\n";

        $updated = $this->command->upsertEnv($contents, 'BROADCAST_DRIVER', 'centrifugo');

        $this->assertSame("APP_NAME=Laravel\nBROADCAST_DRIVER=centrifugo\n", $updated);
    }

    public function testUpsertEnvironmentVariableAppendsMissingValue(): void
    {
        $contents = "APP_NAME=Laravel\n";

        $updated = $this->command->upsertEnv($contents, 'BROADCAST_CONNECTION', 'centrifugo');

        $this->assertSame("APP_NAME=Laravel\nBROADCAST_CONNECTION=centrifugo\n", $updated);
    }

    public function testBroadcastingInstallOptionsAreNonInteractive(): void
    {
        $this->assertSame([
            '--reverb' => true,
            '--without-reverb' => true,
            '--without-node' => true,
            '--no-interaction' => true,
        ], $this->command->installOptions());
    }
}
