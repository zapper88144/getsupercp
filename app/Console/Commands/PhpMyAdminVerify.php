<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PhpMyAdminVerify extends Command
{
    protected $signature = 'phpmyadmin:verify';

    protected $description = 'Verify phpMyAdmin installation and configuration';

    public function handle(): int
    {
        $this->info('phpMyAdmin Installation Status');
        $this->line(str_repeat('=', 40));
        $this->newLine();

        // Check if enabled
        $enabled = config('app.phpmyadmin_enabled', false);
        $this->checkStatus('Enabled', $enabled);

        // Check installation path
        $path = config('app.phpmyadmin_path', '/var/www/phpmyadmin');
        $exists = File::isDirectory($path);
        $this->checkStatus("Path: $path", $exists);

        // Check config file
        $configFile = $path.'/config.inc.php';
        $configExists = File::exists($configFile);
        $this->checkStatus("Config file: $configFile", $configExists);

        // Try database connection
        try {
            DB::connection()->getPdo();
            $this->checkStatus('Database Connection', true);
        } catch (\Exception $e) {
            $this->checkStatus('Database Connection', false);
            $this->warn('Error: '.$e->getMessage());
        }

        // Check required PHP extensions
        $this->newLine();
        $this->line('PHP Extensions:');
        $extensions = ['pdo', 'pdo_mysql', 'mysqli', 'openssl', 'json'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->checkExtension($ext, $loaded);
        }

        // Check write permissions
        $this->newLine();
        $this->line('Write Permissions:');
        if ($exists) {
            $tmpDir = $path.'/tmp';
            $writable = File::isWritable($tmpDir) || File::isWritable($path);
            $this->checkStatus('Temporary directory', $writable);
        }

        // Show configuration details
        if ($configExists) {
            $this->newLine();
            $this->line('Configuration Details:');
            $this->line('Web URL: '.env('APP_URL').'/phpmyadmin/');
            $this->line('Admin Route: '.env('APP_URL').'/admin/database/manager');
            $this->line('Database Host: '.config('database.connections.mysql.host', 'localhost'));
            $this->line('Database Port: '.config('database.connections.mysql.port', '3306'));
        }

        // Summary
        $this->newLine();
        $this->line(str_repeat('=', 40));
        if ($enabled && $configExists && $exists) {
            $this->info('✓ phpMyAdmin is properly installed and configured');

            return self::SUCCESS;
        } else {
            $this->error('✗ phpMyAdmin installation is incomplete');

            return self::FAILURE;
        }
    }

    protected function checkStatus(string $label, bool $status): void
    {
        $icon = $status ? '✓' : '✗';
        $color = $status ? 'info' : 'error';
        $this->line("<$color>$icon $label</$color>");
    }

    protected function checkExtension(string $extension, bool $loaded): void
    {
        $icon = $loaded ? '✓' : '✗';
        $color = $loaded ? 'info' : 'error';
        $this->line("<$color>$icon $extension</$color>");
    }
}
