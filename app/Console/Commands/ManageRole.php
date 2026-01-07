<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RoleTemplateService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class ManageRole extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'role:manage
                            {action : Action to perform (list-templates, create, compare)}
                            {template? : Template key for create/compare action}
                            {--role= : Role name for compare action}
                            {--name= : Custom role name for create action}';

    /**
     * The console command description.
     */
    protected $description = 'Manage roles using templates';

    /**
     * Execute the console command.
     */
    public function handle(RoleTemplateService $service): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list-templates' => $this->listTemplates($service),
            'create' => $this->createFromTemplate($service),
            'compare' => $this->compareRole($service),
            default => $this->error('Invalid action. Use: list-templates, create, or compare'),
        };
    }

    /**
     * List available templates
     */
    protected function listTemplates(RoleTemplateService $service): int
    {
        $templates = $service->getTemplates();

        $this->info('═══════════════════════════════════════');
        $this->info('   Available Role Templates');
        $this->info('═══════════════════════════════════════');
        $this->line('');

        $rows = [];
        foreach ($templates as $key => $template) {
            $permissionCount = count($template['permissions'] ?? []);
            if (in_array('*', $template['permissions'] ?? [])) {
                $permissionCount = 'All';
            }

            $rows[] = [
                $key,
                $template['name'],
                $template['name_ar'] ?? $template['name'],
                $permissionCount,
            ];
        }

        $this->table(
            ['Key', 'Name (EN)', 'Name (AR)', 'Permissions'],
            $rows
        );

        $this->line('');
        $this->info('To create a role from template:');
        $this->line('  php artisan role:manage create <template-key>');
        $this->line('');
        $this->info('To view template details:');
        $this->line('  php artisan role:manage compare <template-key>');

        return self::SUCCESS;
    }

    /**
     * Create role from template
     */
    protected function createFromTemplate(RoleTemplateService $service): int
    {
        $templateKey = $this->argument('template');

        if (! $templateKey) {
            $this->error('Please specify a template key');

            return self::FAILURE;
        }

        $template = $service->getTemplateDetails($templateKey);

        if (! $template) {
            $this->error("Template '{$templateKey}' not found");
            $this->line('Use "role:manage list-templates" to see available templates');

            return self::FAILURE;
        }

        $this->info('Creating role from template: '.$template['name']);
        $this->line('');

        $customName = $this->option('name');
        $roleName = $customName ?: $this->ask('Role name', $template['name']);

        // Check if role exists
        if (Role::where('name', $roleName)->exists()) {
            $this->error("Role '{$roleName}' already exists");

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Template Details:');
        $this->line('  Name: '.$template['name']);
        $this->line('  Name (AR): '.($template['name_ar'] ?? $template['name']));
        $this->line('  Description: '.$template['description']);
        $this->line('  Permissions: '.count($template['permissions']));

        if (! $this->confirm('Create this role?', true)) {
            $this->warn('Role creation cancelled');

            return self::SUCCESS;
        }

        try {
            $role = $service->createFromTemplate($templateKey, $roleName);

            $this->info('✓ Role created successfully!');
            $this->line('  ID: '.$role->id);
            $this->line('  Name: '.$role->name);
            $this->line('  Permissions: '.$role->permissions->count());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create role: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Compare role with template
     */
    protected function compareRole(RoleTemplateService $service): int
    {
        $templateKey = $this->argument('template');
        $roleName = $this->option('role');

        if (! $templateKey) {
            $this->error('Please specify a template key');

            return self::FAILURE;
        }

        if (! $roleName) {
            $this->error('Please specify a role name with --role option');

            return self::FAILURE;
        }

        $template = $service->getTemplateDetails($templateKey);

        if (! $template) {
            $this->error("Template '{$templateKey}' not found");

            return self::FAILURE;
        }

        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error("Role '{$roleName}' not found");

            return self::FAILURE;
        }

        $comparison = $service->compareRoleWithTemplate($role, $templateKey);

        $this->info('═══════════════════════════════════════');
        $this->info('   Role vs Template Comparison');
        $this->info('═══════════════════════════════════════');
        $this->line('');
        $this->info('Role: '.$role->name);
        $this->info('Template: '.$template['name']);
        $this->line('');

        $this->info('Match Percentage: '.$comparison['match_percentage'].'%');
        $this->line('');

        if (! empty($comparison['missing_from_role'])) {
            $this->warn('Missing Permissions ('.count($comparison['missing_from_role']).')');
            foreach (array_slice($comparison['missing_from_role'], 0, 10) as $permission) {
                $this->line('  • '.$permission);
            }
            if (count($comparison['missing_from_role']) > 10) {
                $this->line('  ... and '.(count($comparison['missing_from_role']) - 10).' more');
            }
            $this->line('');
        }

        if (! empty($comparison['extra_in_role'])) {
            $this->info('Extra Permissions ('.count($comparison['extra_in_role']).')');
            foreach (array_slice($comparison['extra_in_role'], 0, 10) as $permission) {
                $this->line('  • '.$permission);
            }
            if (count($comparison['extra_in_role']) > 10) {
                $this->line('  ... and '.(count($comparison['extra_in_role']) - 10).' more');
            }
        }

        return self::SUCCESS;
    }
}
