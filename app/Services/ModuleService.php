<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use App\Services\Contracts\ModuleServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Cache;

class ModuleService implements ModuleServiceInterface
{
    use HandlesServiceErrors;

    /** @return array<int, array{key:string,name:string,enabled:bool}> */
    public function allForBranch(?int $branchId = null): array
    {
        $branchId = $branchId ?? request()->attributes->get('branch_id');

        return Cache::remember('modules:b:'.$branchId, 600, function () use ($branchId) {
            if (! $branchId) {
                return [];
            }

            $mods = BranchModule::query()
                ->where('branch_id', $branchId)
                ->get(['module_key', 'enabled']);

            $definitions = collect(config('modules.available', []))
                ->keyBy('key');

            return $mods->map(function (BranchModule $bm) use ($definitions) {
                $def = $definitions->get($bm->module_key, [
                    'key' => $bm->module_key,
                    'name' => ucfirst($bm->module_key),
                ]);

                return [
                    'key' => $bm->module_key,
                    'name' => (string) ($def['name'] ?? ucfirst($bm->module_key)),
                    'enabled' => (bool) $bm->is_enabled,
                ];
            })->all();
        });
    }

    public function isEnabled(string $key, ?int $branchId = null): bool
    {
        $mods = $this->allForBranch($branchId);

        foreach ($mods as $m) {
            if ($m['key'] === $key) {
                return $m['enabled'];
            }
        }

        return false;
    }

    public function ensureModule(string $key, array $attributes = []): Module
    {
        return $this->handleServiceOperation(
            callback: function () use ($key, $attributes) {
                /** @var Module $module */
                $module = Module::firstOrNew(['key' => $key]);

                $module->fill($attributes);
                $module->is_active = $module->is_active ?? true;
                $module->save();

                return $module;
            },
            operation: 'ensureModule',
            context: ['key' => $key, 'attributes' => $attributes]
        );
    }

    public function enableForBranch(Branch $branch, string $moduleKey, array $settings = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $moduleKey, $settings) {
                $module = Module::where('key', $moduleKey)->firstOrFail();

                $branch->modules()->syncWithoutDetaching([
                    $module->id => [
                        'module_key' => $moduleKey,
                        'enabled' => true,
                        'settings' => $settings,
                    ],
                ]);
            },
            operation: 'enableForBranch',
            context: ['branch_id' => $branch->id, 'module_key' => $moduleKey, 'settings' => $settings]
        );
    }

    public function disableForBranch(Branch $branch, string $moduleKey): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $moduleKey) {
                $module = Module::where('key', $moduleKey)->first();

                if (! $module) {
                    return;
                }

                /** @var BranchModule|null $pivot */
                $pivot = $branch->branchModules()
                    ->where('module_id', $module->id)
                    ->first();

                if ($pivot) {
                    $pivot->enabled = false;
                    $pivot->save();
                }
            },
            operation: 'disableForBranch',
            context: ['branch_id' => $branch->id, 'module_key' => $moduleKey]
        );
    }

    public function getBranchModulesConfig(Branch $branch): array
    {
        return $branch->branchModules()
            ->get()
            ->mapWithKeys(function (BranchModule $bm) {
                return [
                    $bm->module_key => [
                        'enabled' => $bm->enabled,
                        'settings' => $bm->settings ?? [],
                    ],
                ];
            })
            ->all();
    }
}
