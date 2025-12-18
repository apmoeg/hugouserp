<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check {--fix : Attempt to fix minor issues}';
    protected $description = 'Comprehensive system health check for ERP integrity';

    protected array $issues = [];
    protected array $warnings = [];
    protected array $suggestions = [];

    public function handle(): int
    {
        $this->info('🔍 Starting comprehensive system health check...');
        $this->newLine();

        // Run all checks
        $this->checkRoutes();
        $this->checkModels();
        $this->checkMigrations();
        $this->checkTranslations();
        $this->checkPermissions();
        $this->checkDatabaseConnections();
        $this->checkFilePermissions();

        // Display results
        $this->displayResults();

        return empty($this->issues) ? Command::SUCCESS : Command::FAILURE;
    }

    protected function checkRoutes(): void
    {
        $this->info('📍 Checking routes...');
        
        $routes = Route::getRoutes();
        $namedRoutes = [];
        $duplicates = [];

        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name) {
                if (isset($namedRoutes[$name])) {
                    $duplicates[] = $name;
                }
                $namedRoutes[$name] = true;
            }
        }

        if (!empty($duplicates)) {
            $this->issues[] = 'Duplicate route names found: ' . implode(', ', array_unique($duplicates));
        }

        $this->info('  ✓ Checked ' . count($namedRoutes) . ' named routes');
    }

    protected function checkModels(): void
    {
        $this->info('📦 Checking models...');
        
        $modelPath = app_path('Models');
        if (!File::exists($modelPath)) {
            $this->warnings[] = 'Models directory not found';
            return;
        }

        $models = File::files($modelPath);
        $checked = 0;

        foreach ($models as $model) {
            $className = 'App\\Models\\' . $model->getFilenameWithoutExtension();
            if (class_exists($className)) {
                $checked++;
            } else {
                $this->issues[] = "Model class not found: $className";
            }
        }

        $this->info("  ✓ Checked $checked models");
    }

    protected function checkMigrations(): void
    {
        $this->info('🗄️  Checking migrations...');
        
        try {
            $ran = DB::table('migrations')->count();
            $this->info("  ✓ $ran migrations applied");
        } catch (\Exception $e) {
            $this->issues[] = 'Cannot check migrations: ' . $e->getMessage();
        }
    }

    protected function checkTranslations(): void
    {
        $this->info('🌍 Checking translations...');
        
        $enFile = lang_path('en.json');
        $arFile = lang_path('ar.json');

        if (!File::exists($enFile)) {
            $this->issues[] = 'English translation file not found';
            return;
        }

        if (!File::exists($arFile)) {
            $this->issues[] = 'Arabic translation file not found';
            return;
        }

        $enTranslations = json_decode(File::get($enFile), true);
        $arTranslations = json_decode(File::get($arFile), true);

        $enKeys = array_keys($enTranslations);
        $arKeys = array_keys($arTranslations);

        $missingInAr = array_diff($enKeys, $arKeys);
        $missingInEn = array_diff($arKeys, $enKeys);

        if (!empty($missingInAr)) {
            $this->warnings[] = count($missingInAr) . ' translations missing in Arabic';
            if (count($missingInAr) <= 10) {
                $this->suggestions[] = 'Missing AR translations: ' . implode(', ', array_slice($missingInAr, 0, 10));
            }
        }

        if (!empty($missingInEn)) {
            $this->warnings[] = count($missingInEn) . ' translations missing in English';
        }

        $this->info("  ✓ EN: " . count($enTranslations) . " translations");
        $this->info("  ✓ AR: " . count($arTranslations) . " translations");
    }

    protected function checkPermissions(): void
    {
        $this->info('🔐 Checking permissions...');
        
        try {
            $permissions = DB::table('permissions')->count();
            $roles = DB::table('roles')->count();
            $this->info("  ✓ $permissions permissions, $roles roles");
        } catch (\Exception $e) {
            $this->warnings[] = 'Cannot check permissions: ' . $e->getMessage();
        }
    }

    protected function checkDatabaseConnections(): void
    {
        $this->info('💾 Checking database connection...');
        
        try {
            DB::connection()->getPdo();
            $this->info('  ✓ Database connected');
        } catch (\Exception $e) {
            $this->issues[] = 'Database connection failed: ' . $e->getMessage();
        }
    }

    protected function checkFilePermissions(): void
    {
        $this->info('📁 Checking file permissions...');
        
        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                $this->issues[] = "Directory not found: $directory";
                continue;
            }

            if (!is_writable($directory)) {
                $this->issues[] = "Directory not writable: $directory";
            }
        }

        $this->info('  ✓ Checked ' . count($directories) . ' directories');
    }

    protected function displayResults(): void
    {
        $this->newLine();
        
        if (!empty($this->issues)) {
            $this->error('❌ Issues Found (' . count($this->issues) . '):');
            foreach ($this->issues as $issue) {
                $this->line('  - ' . $issue);
            }
            $this->newLine();
        }

        if (!empty($this->warnings)) {
            $this->warn('⚠️  Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line('  - ' . $warning);
            }
            $this->newLine();
        }

        if (!empty($this->suggestions)) {
            $this->info('💡 Suggestions (' . count($this->suggestions) . '):');
            foreach ($this->suggestions as $suggestion) {
                $this->line('  - ' . $suggestion);
            }
            $this->newLine();
        }

        if (empty($this->issues) && empty($this->warnings)) {
            $this->info('✅ System health check passed! No issues found.');
        }
    }
}
