<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Contracts\ModuleFieldServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Services\ModuleFieldService;
// Models & observers
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Model;
// Services
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(ModuleFieldServiceInterface::class, ModuleFieldService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        JsonResource::withoutWrapping();

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        if (app()->environment('local')) {
            Model::shouldBeStrict();
            Model::preventSilentlyDiscardingAttributes();
            Model::preventAccessingMissingAttributes();
            Model::preventLazyLoading();
        }

        // Observers
        Product::observe(ProductObserver::class);
    }
}
