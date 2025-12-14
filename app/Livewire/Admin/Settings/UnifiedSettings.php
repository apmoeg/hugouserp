<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UnifiedSettings extends Component
{
    public string $activeTab = 'general';

    public array $tabs = [
        'general' => 'General Settings',
        'branch' => 'Branch Settings',
        'currencies' => 'Currencies',
        'rates' => 'Exchange Rates',
        'translations' => 'Translations',
        'security' => 'Security',
        'backup' => 'Backup',
        'advanced' => 'Advanced',
    ];

    // General settings
    public string $company_name = '';
    public string $company_email = '';
    public string $company_phone = '';
    public string $timezone = 'UTC';
    public string $date_format = 'Y-m-d';
    public string $default_currency = 'USD';

    // Branch settings
    public bool $multi_branch = false;
    public bool $require_branch_selection = true;

    // Security settings
    public bool $require_2fa = false;
    public int $session_timeout = 120;
    public bool $enable_audit_log = true;

    // Advanced settings
    public bool $enable_api = true;
    public bool $enable_webhooks = false;
    public int $cache_ttl = 3600;

    // Backup settings
    public bool $auto_backup = false;
    public string $backup_frequency = 'daily';
    public int $backup_retention_days = 30;
    public string $backup_storage = 'local';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('settings.view')) {
            abort(403);
        }

        // Get tab from query string
        $this->activeTab = request()->query('tab', 'general');
        
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        // Bulk load all settings for performance
        $settings = Cache::remember('system_settings_all', 3600, function () {
            return SystemSetting::pluck('value', 'key')->toArray();
        });

        // Load general settings
        $this->company_name = $settings['company.name'] ?? config('app.name', 'HugouERP');
        $this->company_email = $settings['company.email'] ?? '';
        $this->company_phone = $settings['company.phone'] ?? '';
        $this->timezone = $settings['app.timezone'] ?? config('app.timezone', 'UTC');
        $this->date_format = $settings['app.date_format'] ?? 'Y-m-d';
        $this->default_currency = $settings['general.default_currency'] ?? 'USD';

        // Load other settings
        $this->multi_branch = (bool) ($settings['system.multi_branch'] ?? false);
        $this->require_branch_selection = (bool) ($settings['system.require_branch_selection'] ?? true);
        $this->require_2fa = (bool) ($settings['security.require_2fa'] ?? false);
        $this->session_timeout = (int) ($settings['security.session_timeout'] ?? 120);
        $this->enable_audit_log = (bool) ($settings['security.enable_audit_log'] ?? true);
        $this->enable_api = (bool) ($settings['advanced.enable_api'] ?? true);
        $this->enable_webhooks = (bool) ($settings['advanced.enable_webhooks'] ?? false);
        $this->cache_ttl = (int) ($settings['advanced.cache_ttl'] ?? 3600);

        // Load backup settings
        $this->auto_backup = (bool) ($settings['backup.auto_backup'] ?? false);
        $this->backup_frequency = $settings['backup.frequency'] ?? 'daily';
        $this->backup_retention_days = (int) ($settings['backup.retention_days'] ?? 30);
        $this->backup_storage = $settings['backup.storage'] ?? 'local';
    }

    protected function getSetting(string $key, $default = null)
    {
        $settings = Cache::remember('system_settings_all', 3600, function () {
            return SystemSetting::pluck('value', 'key')->toArray();
        });
        return $settings[$key] ?? $default;
    }

    protected function setSetting(string $key, $value, string $group = 'general'): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'is_public' => false,
            ]
        );
    }

    public function switchTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'default_currency' => 'required|string|size:3',
        ]);

        $this->setSetting('company.name', $this->company_name, 'general');
        $this->setSetting('company.email', $this->company_email, 'general');
        $this->setSetting('company.phone', $this->company_phone, 'general');
        $this->setSetting('app.timezone', $this->timezone, 'general');
        $this->setSetting('app.date_format', $this->date_format, 'general');
        $this->setSetting('general.default_currency', $this->default_currency, 'general');

        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        session()->flash('success', __('General settings saved successfully'));
    }

    public function saveBranch(): void
    {
        $this->setSetting('system.multi_branch', $this->multi_branch, 'branch');
        $this->setSetting('system.require_branch_selection', $this->require_branch_selection, 'branch');

        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        session()->flash('success', __('Branch settings saved successfully'));
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'session_timeout' => 'required|integer|min:5|max:1440',
        ]);

        $this->setSetting('security.require_2fa', $this->require_2fa, 'security');
        $this->setSetting('security.session_timeout', $this->session_timeout, 'security');
        $this->setSetting('security.enable_audit_log', $this->enable_audit_log, 'security');

        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        session()->flash('success', __('Security settings saved successfully'));
    }

    public function saveAdvanced(): void
    {
        $this->validate([
            'cache_ttl' => 'required|integer|min:60|max:86400',
        ]);

        $this->setSetting('advanced.enable_api', $this->enable_api, 'advanced');
        $this->setSetting('advanced.enable_webhooks', $this->enable_webhooks, 'advanced');
        $this->setSetting('advanced.cache_ttl', $this->cache_ttl, 'advanced');

        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        session()->flash('success', __('Advanced settings saved successfully'));
    }

    public function saveBackup(): void
    {
        $this->validate([
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'backup_storage' => 'required|in:local,s3,ftp',
        ]);

        $this->setSetting('backup.auto_backup', $this->auto_backup, 'backup');
        $this->setSetting('backup.frequency', $this->backup_frequency, 'backup');
        $this->setSetting('backup.retention_days', $this->backup_retention_days, 'backup');
        $this->setSetting('backup.storage', $this->backup_storage, 'backup');

        Cache::forget('system_settings');
        Cache::forget('system_settings_all');
        session()->flash('success', __('Backup settings saved successfully'));
    }

    public function render()
    {
        $currencies = \App\Models\Currency::active()->ordered()->get(['code', 'name', 'symbol']);
        
        return view('livewire.admin.settings.unified-settings', [
            'currencies' => $currencies,
        ]);
    }
}
