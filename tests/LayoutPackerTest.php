<?php

use JohnRivera7\FilamentWidgetGrid\FilamentWidgetGridPlugin;
use JohnRivera7\FilamentWidgetGrid\Support\LayoutPacker;
use JohnRivera7\FilamentWidgetGrid\Support\WidgetInspector;

it('packs widgets into the first available grid cells', function () {
    $items = LayoutPacker::normalize([
        ['widget' => 'StatsWidget', 'w' => 6, 'h' => 2],
        ['widget' => 'ChartWidget', 'w' => 6, 'h' => 2],
        ['widget' => 'TableWidget', 'w' => 12, 'h' => 3],
    ], columns: 12, maxHeight: 8);

    expect($items)->toHaveCount(3)
        ->and($items[0])->toMatchArray(['widget' => 'StatsWidget', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 2])
        ->and($items[1])->toMatchArray(['widget' => 'ChartWidget', 'x' => 6, 'y' => 0, 'w' => 6, 'h' => 2])
        ->and($items[2])->toMatchArray(['widget' => 'TableWidget', 'x' => 0, 'y' => 2, 'w' => 12, 'h' => 3]);
});

it('keeps explicit coordinates from dash-arrange rows', function () {
    $item = LayoutPacker::fromDashArrange([
        'widget_name' => 'App\\Filament\\Widgets\\OrdersWidget',
        'column_span' => 4,
        'row_span' => 3,
        'grid_column_start' => 5,
        'grid_row_start' => 2,
        'show_widget' => true,
    ]);

    expect($item)->toMatchArray([
        'widget' => 'App\\Filament\\Widgets\\OrdersWidget',
        'x' => 4,
        'y' => 1,
        'w' => 4,
        'h' => 3,
        'visible' => true,
    ]);
});

it('clamps oversized widgets to the column count', function () {
    $items = LayoutPacker::normalize([
        ['widget' => 'WideWidget', 'w' => 99, 'h' => 99, 'x' => 0, 'y' => 0],
    ], columns: 12, maxHeight: 8);

    expect($items[0]['w'])->toBe(12)
        ->and($items[0]['h'])->toBe(8);
});

it('identifies the plugin with a stable filament id', function () {
    expect(FilamentWidgetGridPlugin::make()->getId())->toBe('filament-widget-grid');
});

it('defaults to 24 columns and a permission-aware view callback', function () {
    $plugin = FilamentWidgetGridPlugin::make()
        ->canViewWidget(fn (string $widget): bool => $widget === 'AllowedWidget');

    expect($plugin->getColumns())->toBe(24)
        ->and($plugin->getCellHeight())->toBe(45)
        ->and($plugin->shouldFloat())->toBeTrue()
        ->and($plugin->userCanViewWidget('AllowedWidget'))->toBeTrue()
        ->and($plugin->userCanViewWidget('DeniedWidget'))->toBeFalse();
});

it('keeps holes so widgets can sit in leftover collage space', function () {
    $items = LayoutPacker::normalize([
        ['widget' => 'Wide', 'x' => 0, 'y' => 0, 'w' => 16, 'h' => 4],
        ['widget' => 'Tiny', 'x' => 18, 'y' => 1, 'w' => 4, 'h' => 2],
    ], columns: 24, maxHeight: 60);

    expect($items[0])->toMatchArray(['widget' => 'Wide', 'x' => 0, 'y' => 0, 'w' => 16, 'h' => 4])
        ->and($items[1])->toMatchArray(['widget' => 'Tiny', 'x' => 18, 'y' => 1, 'w' => 4, 'h' => 2]);
});

it('scales a 12-column layout onto the finer collage grid', function () {
    $scaled = LayoutPacker::scale([
        ['widget' => 'StatsWidget', 'x' => 4, 'y' => 1, 'w' => 4, 'h' => 1, 'visible' => true],
    ], fromColumns: 12, toColumns: 24, fromCellHeight: 180, toCellHeight: 45);

    expect($scaled[0])->toMatchArray([
        'widget' => 'StatsWidget',
        'x' => 8,
        'y' => 4,
        'w' => 8,
        'h' => 4,
        'visible' => true,
    ]);
});

it('unwraps legacy stored layouts as 12 columns', function () {
    $unwrapped = LayoutPacker::unwrapFromStorage([
        ['widget' => 'StatsWidget', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 1, 'visible' => true],
    ]);

    expect($unwrapped['columns'])->toBe(12)
        ->and($unwrapped['cellHeight'])->toBe(180)
        ->and($unwrapped['items'])->toHaveCount(1);
});

it('uses explicit gridW and gridH as the default cell size', function () {
    $widget = new class
    {
        public static int $gridW = 14;

        public static int $gridH = 10;
    };

    expect(WidgetInspector::defaultWidth($widget::class, 24))->toBe(14)
        ->and(WidgetInspector::defaultHeight($widget::class, 45))->toBe(10);
});

it('inflates leftover 4x4 auto sizes to the widget default', function () {
    $widget = new class
    {
        public static int $gridW = 14;

        public static int $gridH = 10;
    };

    $item = WidgetInspector::inflateLegacySize([
        'widget' => $widget::class,
        'x' => 2,
        'y' => 3,
        'w' => 4,
        'h' => 4,
        'visible' => true,
    ], 24, 45, 60);

    expect($item)->toMatchArray([
        'widget' => $widget::class,
        'x' => -1,
        'y' => -1,
        'w' => 14,
        'h' => 10,
        'visible' => true,
    ]);
});

it('keeps a layout the user already resized', function () {
    $widget = new class
    {
        public static int $gridW = 14;

        public static int $gridH = 10;
    };

    $item = WidgetInspector::inflateLegacySize([
        'widget' => $widget::class,
        'x' => 0,
        'y' => 0,
        'w' => 8,
        'h' => 8,
        'visible' => true,
    ], 24, 45, 60);

    expect($item['w'])->toBe(8)
        ->and($item['h'])->toBe(8)
        ->and($item['x'])->toBe(0);
});

it('uses compact density for tighter rows', function () {
    expect(FilamentWidgetGridPlugin::make()->density('compact')->getCellHeight())->toBe(32)
        ->and(FilamentWidgetGridPlugin::make()->density('comfortable')->getCellHeight())->toBe(45)
        ->and(FilamentWidgetGridPlugin::make()->density('compact')->getDensity())->toBe('compact');
});

it('sizes non-chart widgets to their content', function () {
    $widget = new class
    {
        //
    };

    expect(WidgetInspector::sizeToContent($widget::class))->toBeTrue();
});

it('reads optional grid minimums from the widget class', function () {
    $widget = new class
    {
        public static int $gridMinW = 10;

        public static int $gridMinH = 8;
    };

    expect(WidgetInspector::gridMinWidth($widget::class, 24))->toBe(10)
        ->and(WidgetInspector::gridMinHeight($widget::class, 60))->toBe(8)
        ->and(WidgetInspector::gridMinWidth('MissingWidget', 24))->toBe(1);
});
