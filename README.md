<div class="filament-hidden">

# Widget Grid

</div>

Let users **add, move, and resize** Filament widgets on a fine 24-column collage grid, with **permission checks** for which widgets they can place.

This is not a report builder. You keep your existing Filament widgets; the plugin stores each user's layout and gives them a real drag-and-drop canvas with resize handles.

Compatible with **Filament v5** and PHP **8.2+**.

![Widget Grid collage dashboard](https://raw.githubusercontent.com/Johnrivera7/filament-widget-grid/main/images/cover.jpg)

## Installation

```bash
composer require johnrivera7/filament-widget-grid
php artisan filament-widget-grid:install
```

Register the plugin on your panel:

```php
use JohnRivera7\FilamentWidgetGrid\FilamentWidgetGridPlugin;

$panel
    ->plugins([
        FilamentWidgetGridPlugin::make()
            ->canViewWidget(fn (string $widget): bool => auth()->user()?->can('view', $widget) ?? false)
            ->canCustomize(fn (): bool => auth()->check())
            ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
    ]);
```

Use the trait on your dashboard page:

```php
use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class Dashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\OrdersChart::class,
        ];
    }
}
```

`getWidgets()` is the catalog. Users only see widgets that pass `canViewWidget()`.

You can also extend the packaged dashboard page:

```php
use JohnRivera7\FilamentWidgetGrid\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            //
        ];
    }
}
```

## Features

- Drag widgets into leftover gaps and resize from every edge on a 24-column collage grid
- Search the permission-filtered catalog; reset a personal layout back to the panel default
- Snap guides while editing; press Escape to cancel; phones stack in one column (edit on larger screens)
- Stats, tables, and custom widgets shrink to their content; charts keep a readable default height
- Per-user layouts, a panel-wide default, lock-the-layout for everyone, and shareable templates
- Import from `shreejan/dash-arrange` (`php artisan filament-widget-grid:import-dash-arrange`)

## Customization

```php
FilamentWidgetGridPlugin::make()
    ->columns(24)
    ->density('comfortable') // or 'compact'
    ->cellHeight(45)
    ->maxHeight(60)
    ->float()
    ->templates()
    ->userIdUsing(fn () => auth()->id());
```

Open the editor from a user-menu link with `/?customize=1` (or `?customize=1` on the dashboard route).

Override extra header actions without dropping the plugin buttons:

```php
protected function getAdditionalHeaderActions(): array
{
    return [
        // your actions
    ];
}
```

## Responsive widgets

The grid only sizes the **cell**. Charts still have to fill that cell.

ApexCharts `responsive` breakpoints use the **browser window**, not the widget width. Give a chart a comfortable default size, and a minimum so users cannot squeeze it unreadably:

```php
class OrdersByCampusChart extends ApexChartWidget
{
    public static int $gridW = 14; // default width on the 24-column collage
    public static int $gridH = 10; // default height in 45px rows
    public static int $gridMinW = 6;
    public static int $gridMinH = 6;
}
```

Without `$gridW` / `$gridH`, stats widgets start full-width, charts at half width and ~450px tall, and tables at two-thirds width. Tiny leftover 4×4 auto-layouts are inflated on load.

In `getOptions()` prefer `redrawOnParentResize: true` with `chart.width: '100%'`. The plugin reflows ApexCharts and Chart.js when a cell is resized. Bar charts also flip to horizontal when the cell is taller than it is wide. Narrow cells shorten legends and hide overlapping labels.

This package is **not a report builder**. Users pick and arrange widgets that you register in `getWidgets()`. There is no UI to create a chart from an Eloquent model or a join; that stays in PHP.

## Migrating from dash-arrange

1. Install and migrate this plugin.
2. Point the dashboard at `HasWidgetGrid` instead of `HasDashArrange`.
3. Run `php artisan filament-widget-grid:import-dash-arrange --panel=admin`.
4. Confirm layouts, then remove `shreejan/dash-arrange`.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Security

See [SECURITY](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE.md).
