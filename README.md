<div class="filament-hidden">

![Widget Drag Fit](https://raw.githubusercontent.com/Johnrivera7/filament-widget-drag-fit/main/images/cover.jpg)

# Widget Drag Fit

</div>

**Widget Drag Fit** — *draggable widgets that fit your dashboard*.

Turn any Filament dashboard into a **collage canvas**: users pick which widgets to show, then **drag**, **resize**, and **save** a layout that fits how they work. You keep writing normal Filament widgets; the plugin wraps them in a grid, stores layouts per user, and enforces who can see or edit what.

Compatible with **Filament v5** and PHP **8.2+**. Translations: **English** and **Spanish**. Dark mode supported.

![Drag and resize demo](https://raw.githubusercontent.com/Johnrivera7/filament-widget-drag-fit/main/images/demo.gif)

![Light mode](https://raw.githubusercontent.com/Johnrivera7/filament-widget-drag-fit/main/images/screenshot-light.jpg)

![Dark mode](https://raw.githubusercontent.com/Johnrivera7/filament-widget-drag-fit/main/images/screenshot-dark.jpg)

![Customize mode — catalog, templates, and admin actions](https://raw.githubusercontent.com/Johnrivera7/filament-widget-drag-fit/main/images/screenshot-editor.jpg)

## What it does

### Collage grid (drag & resize)

- **24-column** grid with fine **45px** rows (comfortable) or **32px** (compact density)
- Drag widgets freely; **float mode** keeps leftover gaps so you can drop a small widget into a hole
- Resize from **all edges and corners** (N, S, E, W, and diagonals)
- Grab cursor: open hand on hover, closed hand while dragging
- Escape cancels customize mode
- On phones (**&lt; 768px**): widgets stack in one column; drag/resize is disabled with a short note

### Widget catalog

- Catalog comes from your dashboard `getWidgets()` list
- Each user only sees widgets that pass `canViewWidget()`
- Search box to filter the catalog while editing
- Toggle widgets on/off with checkboxes; remove with the red **×** on a cell

### Layouts that persist

| Layout type | Who it is for |
|---|---|
| **Personal** | Each user’s saved collage (`Save for me`) |
| **Panel default** | Company-wide starting layout (`Save as default for everyone`) |
| **Apply to all** | Overwrites every user’s personal layout with the default |
| **Auto-packed** | If nothing is saved yet, widgets are packed automatically by type |

Layouts are stored with column/cell-height metadata so older 12-column designs migrate onto the finer grid.

### Templates

- **Save as template** — snapshot the current collage under a name
- **Restore** — apply one of your templates
- **Share / Stop sharing** — publish a template for others on the same panel
- **Shared templates** — apply a layout another user shared (updates your desktop)

Templates can be disabled with `->templates(false)`.

### Admin controls (avatar menu)

| Action | Who sees it | What it does |
|---|---|---|
| **Customize dashboard** | Users allowed to customize (and when not locked) | Opens `/?customize=1` |
| **Lock / Unlock customization** | Users who manage defaults | When locked, normal users cannot edit the collage; default managers still can |

### Header actions while editing

| Action | Role |
|---|---|
| **Save for me** | Persist personal layout and leave edit mode |
| **Cancel** | Discard unsaved edits and reload the stored layout |
| **Reset to default** | Delete personal layout and fall back to the panel default |
| **Save as default for everyone** | Store the current collage as the panel default |
| **Apply default to all users** | Push that default onto every personal layout |

Add your own header buttons with `getAdditionalHeaderActions()` without removing these.

### Charts and stats that follow the cell

No extra code is required on each widget for move/resize. The grid:

- Reflows **ApexCharts** (and Chart.js canvases) when a cell is resized
- Moves **donut/pie legends under** the chart when the cell is narrow or tall
- Adapts Filament **stats grids** to 4 → 2 → 1 columns as the cell gets narrower or more portrait
- Stretches cards so vertical spacing matches horizontal gutters

Optional author hints (starting size only; not required):

```php
public static int $gridW = 12;              // starting width (of 24)
public static int $gridH = 10;              // starting height (rows)
public static int $gridMinH = 4;            // optional minimum height
public static bool $gridSizeToContent = true; // hug content height (opt-in)
```

`$gridMinW` is **ignored** so users can always shrink width. Default sizes by type: stats ≈ full width, charts ≈ half width / ~450px tall, tables ≈ 2/3 width.

### Permissions (callbacks)

Wire these when registering the plugin:

- `canViewWidget(fn (string $class): bool)` — catalog + visibility on the canvas
- `canCustomize(fn (): bool)` — who may open the editor
- `canManageDefaults(fn (): bool)` — default layout, apply-all, lock toggle
- `canShareTemplates(fn (): bool)` — who may share templates
- `userIdUsing(fn ()` — custom user id if you are not using `auth()->id()`

### Configuration options

```php
FilamentWidgetGridPlugin::make()
    ->columns(24)                 // 1–48
    ->cellHeight(45)              // px per row
    ->maxHeight(60)               // max rows per widget
    ->density('comfortable')      // or 'compact' (32px rows)
    ->float(true)                 // collage gaps vs packed
    ->templates(true)
    ->canViewWidget(...)
    ->canCustomize(...)
    ->canManageDefaults(...)
    ->canShareTemplates(...);
```

### Import from dash-arrange

If you used `shreejan/dash-arrange`:

```bash
php artisan filament-widget-grid:import-dash-arrange --panel=admin
```

Existing layouts are scaled into the 24-column collage.

---

## Installation

```bash
composer require johnrivera7/filament-widget-drag-fit
php artisan filament-widget-grid:install
```

Register the plugin:

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

Use the trait on your dashboard:

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

    protected function getAdditionalHeaderActions(): array
    {
        return [
            // optional extra actions
        ];
    }
}
```

`getWidgets()` is the **catalog**. Open the editor from the avatar menu or visit `/?customize=1`.

---

## What this is not

This is **not** a report builder. Users do not create charts from Eloquent models in the UI. You build Filament widgets in PHP; they arrange those widgets on the desktop.

---

## License

MIT
