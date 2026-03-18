<?php

namespace Modules\Soins\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Illuminate\Support\Facades\Gate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Soins\App\Models\Consultation;
use Modules\Soins\App\Models\AccouchementRequest;
use Modules\Soins\App\Models\HospitalisationRequest;
use Modules\Soins\App\Models\ActeOperatoireRequest;
use Modules\Soins\App\Policies\ConsultationRequestPolicy;
use Modules\Soins\App\Policies\ConsultationPolicy;
use Modules\Soins\App\Policies\AccouchementRequestPolicy;
use Modules\Soins\App\Policies\HospitalisationRequestPolicy;
use Modules\Soins\App\Policies\ActeOperatoireRequestPolicy;
use Modules\Soins\App\Models\Accouchement;
use Modules\Soins\App\Models\ActeOperatoire;
use Modules\Soins\App\Models\Hospitalisation;
use Modules\Soins\App\Policies\AccouchementPolicy;
use Modules\Soins\App\Policies\ActeOperatoirePolicy;
use Modules\Soins\App\Policies\HospitalisationPolicy;
use Modules\Soins\App\Models\Pansement;
use Modules\Soins\App\Models\PansementRequest;
use Modules\Soins\App\Models\Kinesitherapie;
use Modules\Soins\App\Models\KinesitherapieRequest;
use Modules\Soins\App\Policies\PansementPolicy;
use Modules\Soins\App\Policies\PansementRequestPolicy;
use Modules\Soins\App\Policies\KinesitherapiePolicy;
use Modules\Soins\App\Policies\KinesitherapieRequestPolicy;

class SoinsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Soins';
    protected string $nameLower = 'soins';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'src/database/migrations'));

        Gate::policy(ConsultationRequest::class, ConsultationRequestPolicy::class);
        Gate::policy(Consultation::class, ConsultationPolicy::class);
        Gate::policy(AccouchementRequest::class, AccouchementRequestPolicy::class);
        Gate::policy(HospitalisationRequest::class, HospitalisationRequestPolicy::class);
        Gate::policy(ActeOperatoireRequest::class, ActeOperatoireRequestPolicy::class);
        Gate::policy(Accouchement::class, AccouchementPolicy::class);
        Gate::policy(ActeOperatoire::class, ActeOperatoirePolicy::class);
        Gate::policy(Hospitalisation::class, HospitalisationPolicy::class);
        Gate::policy(PansementRequest::class, PansementRequestPolicy::class);
        Gate::policy(Pansement::class, PansementPolicy::class);
        Gate::policy(KinesitherapieRequest::class, KinesitherapieRequestPolicy::class);
        Gate::policy(Kinesitherapie::class, KinesitherapiePolicy::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void {}

    protected function registerCommandSchedules(): void {}

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));
        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config     = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments   = explode('.', $this->nameLower.'.'.$config_key);
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) $normalized[] = $segment;
                    }
                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);
                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    protected function merge_config_from(string $path, string $key): void
    {
        config([$key => array_replace_recursive(config($key, []), require $path)]);
    }

    public function registerViews(): void
    {
        $viewPath   = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');
        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    public function provides(): array { return []; }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) $paths[] = $path.'/modules/'.$this->nameLower;
        }
        return $paths;
    }
}