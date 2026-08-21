<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Filesystem\Filesystem;
use JohnRivera7\FilamentWidgetGrid\Commands\ImportDashArrangeCommand;
use JohnRivera7\FilamentWidgetGrid\Testing\TestsWidgetGrid;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentWidgetGridServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-widget-grid';

    public static string $viewNamespace = 'filament-widget-grid';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('Johnrivera7/filament-custom-dashboard-widgets');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-widget-grid/{$file->getFilename()}"),
                ], 'filament-widget-grid-stubs');
            }
        }

        Testable::mixin(new TestsWidgetGrid);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'johnrivera7/filament-custom-dashboard-widgets';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('filamentWidgetGrid', __DIR__ . '/../resources/dist/filament-widget-grid.js'),
            Css::make('filament-widget-grid-styles', __DIR__ . '/../resources/dist/filament-widget-grid.css')
                ->loadedOnRequest(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            ImportDashArrangeCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_widget_grid_tables',
        ];
    }
}
