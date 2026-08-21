<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid;

use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Facades\Schema;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridSetting;

class FilamentWidgetGridPlugin implements Plugin
{
    /**
     * @var Closure(string): bool|null
     */
    protected ?Closure $canViewWidgetUsing = null;

    /**
     * @var Closure(): bool|null
     */
    protected ?Closure $canCustomizeUsing = null;

    /**
     * @var Closure(): bool|null
     */
    protected ?Closure $canManageDefaultsUsing = null;

    /**
     * @var Closure(): bool|null
     */
    protected ?Closure $canShareTemplatesUsing = null;

    /**
     * @var Closure(): (int|string|null)|null
     */
    protected ?Closure $userIdUsing = null;

    protected int $columns = 24;

    protected int $cellHeight = 45;

    protected int $maxHeight = 60;

    protected bool $float = true;

    protected bool $templates = true;

    /**
     * @var 'comfortable'|'compact'
     */
    protected string $density = 'comfortable';

    public function getId(): string
    {
        return 'filament-widget-grid';
    }

    public function register(Panel $panel): void
    {
        $panel->userMenuItems([
            Action::make('widgetGridCustomize')
                ->label(fn (): string => __('filament-widget-grid::widget-grid.customize'))
                ->icon(Heroicon::SquaresPlus)
                ->sort(0)
                ->url(fn (): string => $this->dashboardCustomizeUrl($panel))
                ->visible(fn (): bool => $this->shouldShowCustomizeMenuItem($panel)),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * @param  Closure(string): bool  $callback
     */
    public function canViewWidget(Closure $callback): static
    {
        $this->canViewWidgetUsing = $callback;

        return $this;
    }

    public function userCanViewWidget(string $widgetClass): bool
    {
        if ($this->canViewWidgetUsing instanceof Closure) {
            return (bool) ($this->canViewWidgetUsing)($widgetClass);
        }

        if (! class_exists($widgetClass)) {
            return false;
        }

        if (is_subclass_of($widgetClass, Widget::class)) {
            return (bool) $widgetClass::canView();
        }

        return true;
    }

    /**
     * @param  Closure(): bool  $callback
     */
    public function canCustomize(Closure $callback): static
    {
        $this->canCustomizeUsing = $callback;

        return $this;
    }

    public function userCanCustomize(): bool
    {
        if ($this->canCustomizeUsing instanceof Closure) {
            return (bool) ($this->canCustomizeUsing)();
        }

        return (bool) auth()->check();
    }

    /**
     * @param  Closure(): bool  $callback
     */
    public function canManageDefaults(Closure $callback): static
    {
        $this->canManageDefaultsUsing = $callback;

        return $this;
    }

    public function userCanManageDefaults(): bool
    {
        if ($this->canManageDefaultsUsing instanceof Closure) {
            return (bool) ($this->canManageDefaultsUsing)();
        }

        return false;
    }

    /**
     * @param  Closure(): bool  $callback
     */
    public function canShareTemplates(Closure $callback): static
    {
        $this->canShareTemplatesUsing = $callback;

        return $this;
    }

    public function userCanShareTemplates(): bool
    {
        if ($this->canShareTemplatesUsing instanceof Closure) {
            return (bool) ($this->canShareTemplatesUsing)();
        }

        return $this->templates && (bool) auth()->check();
    }

    /**
     * @param  Closure(): (int|string|null)  $callback
     */
    public function userIdUsing(Closure $callback): static
    {
        $this->userIdUsing = $callback;

        return $this;
    }

    public function resolveUserId(): int | string | null
    {
        if ($this->userIdUsing instanceof Closure) {
            return ($this->userIdUsing)();
        }

        return auth()->id();
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, min(48, $columns));

        return $this;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function cellHeight(int $pixels): static
    {
        $this->cellHeight = max(24, $pixels);

        return $this;
    }

    public function getCellHeight(): int
    {
        return $this->cellHeight;
    }

    public function maxHeight(int $rows): static
    {
        $this->maxHeight = max(1, $rows);

        return $this;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    public function density(string $density): static
    {
        $this->density = $density === 'compact' ? 'compact' : 'comfortable';
        $this->cellHeight = $this->density === 'compact' ? 32 : 45;

        return $this;
    }

    public function getDensity(): string
    {
        return $this->density;
    }

    public function float(bool $condition = true): static
    {
        $this->float = $condition;

        return $this;
    }

    public function shouldFloat(): bool
    {
        return $this->float;
    }

    public function templates(bool $condition = true): static
    {
        $this->templates = $condition;

        return $this;
    }

    public function hasTemplates(): bool
    {
        return $this->templates;
    }

    protected function dashboardCustomizeUrl(Panel $panel): string
    {
        $url = rtrim($panel->getUrl(), '/');

        return $url.'/?customize=1';
    }

    protected function shouldShowCustomizeMenuItem(Panel $panel): bool
    {
        if (! $this->userCanCustomize()) {
            return false;
        }

        if ($this->panelCustomizationLocked($panel->getId()) && ! $this->userCanManageDefaults()) {
            return false;
        }

        return true;
    }

    protected function panelCustomizationLocked(string $panelId): bool
    {
        if (! Schema::hasTable('widget_grid_settings')) {
            return false;
        }

        return WidgetGridSetting::isLocked($panelId);
    }

    /**
     * @param  class-string<Widget>|WidgetConfiguration  $widget
     */
    public static function widgetClass(string | WidgetConfiguration $widget): string
    {
        return $widget instanceof WidgetConfiguration ? $widget->widget : $widget;
    }
}
