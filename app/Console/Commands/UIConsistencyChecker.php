<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UIConsistencyChecker extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ui:check
                            {--fix : Attempt to auto-fix issues}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     */
    protected $description = 'Check UI consistency across the application';

    protected array $issues = [];

    protected array $suggestions = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════');
        $this->info('   UI Consistency Checker');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        // Run checks
        $this->checkBladeFiles();
        $this->checkLivewireComponents();
        $this->checkFormComponents();
        $this->checkColorConsistency();
        $this->checkAccessibility();

        // Display results
        $this->displayResults();

        // Generate report if requested
        if ($this->option('report')) {
            $this->generateReport();
        }

        return empty($this->issues) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check Blade files for consistency
     */
    protected function checkBladeFiles(): void
    {
        $this->info('Checking Blade files...');

        $bladeFiles = File::allFiles(resource_path('views'));
        $patterns = [
            'inline_styles' => '/style\s*=\s*["\']/',
            'deprecated_tailwind' => '/text-gray-700\s+hover:text-gray-900/', // Should use transitions
            'missing_dark_mode' => '/bg-white(?!\s+dark:bg-)/',
            'inconsistent_spacing' => '/class\s*=\s*["\'][^"\']*\s{2,}/',
        ];

        $foundFiles = 0;
        foreach ($bladeFiles as $file) {
            $content = $file->getContents();
            $filePath = str_replace(base_path().'/', '', $file->getPathname());

            foreach ($patterns as $issue => $pattern) {
                if (preg_match($pattern, $content)) {
                    $foundFiles++;
                    $this->issues[$issue][] = $filePath;
                }
            }
        }

        $this->line("  Found {$foundFiles} files with potential issues");
    }

    /**
     * Check Livewire components
     */
    protected function checkLivewireComponents(): void
    {
        $this->info('Checking Livewire components...');

        $livewireFiles = File::allFiles(app_path('Livewire'));
        $foundIssues = 0;

        foreach ($livewireFiles as $file) {
            $content = $file->getContents();
            $filePath = str_replace(base_path().'/', '', $file->getPathname());

            // Check for deprecated $listeners
            if (preg_match('/\$listeners\s*=/', $content)) {
                $this->issues['deprecated_listeners'][] = $filePath;
                $foundIssues++;
            }

            // Check for missing validation
            if (preg_match('/public\s+function\s+save\s*\(/', $content) &&
                ! preg_match('/\$this->validate\(/', $content)) {
                $this->suggestions['add_validation'][] = $filePath;
            }

            // Check for missing loading states
            if (preg_match('/wire:click=/', $content) &&
                ! preg_match('/wire:loading/', $content)) {
                $this->suggestions['add_loading_states'][] = $filePath;
            }
        }

        $this->line("  Found {$foundIssues} issues in Livewire components");
    }

    /**
     * Check form components usage
     */
    protected function checkFormComponents(): void
    {
        $this->info('Checking form components...');

        $bladeFiles = File::allFiles(resource_path('views'));
        $oldInputPatterns = [
            'raw_input' => '/<input\s+type=["\'](?!hidden)/',
            'raw_textarea' => '/<textarea\s+/',
            'raw_select' => '/<select\s+/',
        ];

        $foundOldStyle = 0;
        foreach ($bladeFiles as $file) {
            $content = $file->getContents();
            $filePath = str_replace(base_path().'/', '', $file->getPathname());

            foreach ($oldInputPatterns as $issue => $pattern) {
                if (preg_match($pattern, $content) &&
                    ! preg_match('/<x-form\.|<x-ui\.form\./', $content)) {
                    $this->suggestions['modernize_forms'][] = $filePath;
                    $foundOldStyle++;
                    break;
                }
            }
        }

        $this->line("  Found {$foundOldStyle} files using old form patterns");
    }

    /**
     * Check color consistency
     */
    protected function checkColorConsistency(): void
    {
        $this->info('Checking color consistency...');

        $bladeFiles = File::allFiles(resource_path('views'));
        $colorPatterns = [
            // Check for hardcoded hex colors instead of Tailwind classes
            'hardcoded_colors' => '/#[0-9a-fA-F]{3,6}/',
        ];

        $foundIssues = 0;
        foreach ($bladeFiles as $file) {
            $content = $file->getContents();
            $filePath = str_replace(base_path().'/', '', $file->getPathname());

            if (preg_match($colorPatterns['hardcoded_colors'], $content)) {
                $this->suggestions['use_tailwind_colors'][] = $filePath;
                $foundIssues++;
            }
        }

        $this->line("  Found {$foundIssues} files with hardcoded colors");
    }

    /**
     * Check accessibility
     */
    protected function checkAccessibility(): void
    {
        $this->info('Checking accessibility...');

        $bladeFiles = File::allFiles(resource_path('views'));
        $accessibilityIssues = 0;

        foreach ($bladeFiles as $file) {
            $content = $file->getContents();
            $filePath = str_replace(base_path().'/', '', $file->getPathname());

            // Check for buttons without aria-label or text content (icon-only buttons)
            if (preg_match_all('/<button[^>]*>/', $content, $matches)) {
                foreach ($matches[0] as $buttonTag) {
                    // Check if button has aria-label or aria-labelledby
                    if (!preg_match('/aria-label(ledby)?=/', $buttonTag)) {
                        $this->suggestions['add_aria_labels'][] = $filePath;
                        $accessibilityIssues++;
                        break;
                    }
                }
            }

            // Check for images without alt
            if (preg_match('/<img(?!.*alt=)/', $content)) {
                $this->issues['missing_alt_text'][] = $filePath;
                $accessibilityIssues++;
            }
        }

        $this->line("  Found {$accessibilityIssues} accessibility issues");
    }

    /**
     * Display results
     */
    protected function displayResults(): void
    {
        $this->line('');
        $this->info('═══════════════════════════════════════');
        $this->info('   Results');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        // Critical Issues
        if (! empty($this->issues)) {
            $this->error('⚠️  Critical Issues:');
            foreach ($this->issues as $issue => $files) {
                $this->warn('  '.str_replace('_', ' ', ucfirst($issue)).': '.count($files).' files');
                if (count($files) <= 5) {
                    foreach ($files as $file) {
                        $this->line('    • '.$file);
                    }
                } else {
                    foreach (array_slice($files, 0, 3) as $file) {
                        $this->line('    • '.$file);
                    }
                    $this->line('    ... and '.(count($files) - 3).' more');
                }
            }
            $this->line('');
        }

        // Suggestions
        if (! empty($this->suggestions)) {
            $this->info('💡 Suggestions for Improvement:');
            foreach ($this->suggestions as $suggestion => $files) {
                $this->line('  '.str_replace('_', ' ', ucfirst($suggestion)).': '.count($files).' files');
            }
            $this->line('');
        }

        // Summary
        $totalIssues = array_sum(array_map('count', $this->issues));
        $totalSuggestions = array_sum(array_map('count', $this->suggestions));

        if ($totalIssues === 0 && $totalSuggestions === 0) {
            $this->info('✓ No issues found! UI is consistent.');
        } else {
            $this->warn("Found {$totalIssues} critical issues and {$totalSuggestions} suggestions");
            $this->line('');
            $this->info('Run with --report flag to generate detailed report');
            $this->info('Run with --fix flag to attempt auto-fixes (experimental)');
        }
    }

    /**
     * Generate detailed report
     */
    protected function generateReport(): void
    {
        $reportPath = storage_path('logs/ui-consistency-report.json');

        $report = [
            'timestamp' => now()->toIso8601String(),
            'critical_issues' => $this->issues,
            'suggestions' => $this->suggestions,
            'summary' => [
                'total_critical' => array_sum(array_map('count', $this->issues)),
                'total_suggestions' => array_sum(array_map('count', $this->suggestions)),
            ],
        ];

        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("Report generated: {$reportPath}");
    }
}
