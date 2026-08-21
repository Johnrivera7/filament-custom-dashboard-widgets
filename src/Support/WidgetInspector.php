<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Support;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\TableWidget;
use Throwable;

final class WidgetInspector
{
    /**
     * @param  class-string  $widgetClass
     */
    public static function title(string $widgetClass): string
    {
        $fallback = class_basename($widgetClass);

        if (! class_exists($widgetClass)) {
            return $fallback;
        }

        try {
            $widget = app($widgetClass);
        } catch (Throwable) {
            return $fallback;
        }

        foreach (['getHeading', 'getLabel', 'getTitle'] as $method) {
            if (! method_exists($widget, $method)) {
                continue;
            }

            try {
                $value = $widget->{$method}();
            } catch (Throwable) {
                continue;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function defaultWidth(string $widgetClass, int $columns = 24): int
    {
        $explicit = self::staticIntProperty($widgetClass, 'gridW', 0, $columns);

        if ($explicit > 0) {
            return $explicit;
        }

        if (self::isStatsWidget($widgetClass)) {
            return $columns;
        }

        if (self::isChartWidget($widgetClass)) {
            return max(1, min($columns, (int) round($columns * 0.5)));
        }

        if (self::isTableWidget($widgetClass)) {
            return max(1, min($columns, (int) round($columns * 2 / 3)));
        }

        $scaled = (int) round(self::filamentColumnSpan($widgetClass) * $columns / 12);

        return max(1, min($columns, max((int) round($columns / 3), $scaled)));
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function defaultHeight(string $widgetClass, int $cellHeight = 45, int $maxHeight = 60): int
    {
        $explicit = self::staticIntProperty($widgetClass, 'gridH', 0, $maxHeight);

        if ($explicit > 0) {
            return $explicit;
        }

        $pixels = match (true) {
            self::isStatsWidget($widgetClass) => 220,
            self::isChartWidget($widgetClass) => 450,
            self::isTableWidget($widgetClass) => 360,
            default => 280,
        };

        return max(2, min($maxHeight, (int) round($pixels / max(1, $cellHeight))));
    }

    /**
     * Old auto-pack used Filament columnSpan (often 1–2) and ~180px tall cells.
     *
     * @param  array{widget: string, x?: int, y?: int, w?: int, h?: int, visible?: bool}  $item
     * @return array{widget: string, x: int, y: int, w: int, h: int, visible: bool}
     */
    public static function inflateLegacySize(array $item, int $columns, int $cellHeight, int $maxHeight): array
    {
        $legacyHeight = max(2, (int) round(180 / max(1, $cellHeight)));
        $width = (int) ($item['w'] ?? 1);
        $height = (int) ($item['h'] ?? 1);
        $widget = $item['widget'] ?? null;

        if (! is_string($widget) || $widget === '' || $height > $legacyHeight || $width > 4) {
            return [
                'widget' => is_string($widget) ? $widget : '',
                'x' => (int) ($item['x'] ?? 0),
                'y' => (int) ($item['y'] ?? 0),
                'w' => $width,
                'h' => $height,
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        return [
            'widget' => $widget,
            'x' => -1,
            'y' => -1,
            'w' => self::defaultWidth($widget, $columns),
            'h' => self::defaultHeight($widget, $cellHeight, $maxHeight),
            'visible' => (bool) ($item['visible'] ?? true),
        ];
    }

    /**
     * The grid never locks width from widget code.
     *
     * @param  class-string  $widgetClass
     */
    public static function gridMinWidth(string $widgetClass, int $columns = 24): int
    {
        unset($widgetClass, $columns);

        return 1;
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function gridMinHeight(string $widgetClass, int $maxHeight = 60): int
    {
        return self::staticIntProperty($widgetClass, 'gridMinH', 1, $maxHeight);
    }

    /**
     * Widgets are freely resized by the grid. Authors may opt into hug-content height.
     *
     * @param  class-string  $widgetClass
     */
    public static function sizeToContent(string $widgetClass): bool
    {
        if (! class_exists($widgetClass)) {
            return false;
        }

        $vars = get_class_vars($widgetClass);

        return isset($vars['gridSizeToContent']) && $vars['gridSizeToContent'] === true;
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function isChartWidget(string $widgetClass): bool
    {
        return self::isSubclassOfAny($widgetClass, [
            ChartWidget::class,
            'Leandrocfe\\FilamentApexCharts\\Widgets\\ApexChartWidget',
        ]);
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function isStatsWidget(string $widgetClass): bool
    {
        return self::isSubclassOfAny($widgetClass, [StatsOverviewWidget::class]);
    }

    /**
     * @param  class-string  $widgetClass
     */
    public static function isTableWidget(string $widgetClass): bool
    {
        return self::isSubclassOfAny($widgetClass, [TableWidget::class]);
    }

    /**
     * @param  class-string  $widgetClass
     */
    private static function staticIntProperty(string $widgetClass, string $property, int $fallback, int $max): int
    {
        if (! class_exists($widgetClass)) {
            return $fallback;
        }

        $vars = get_class_vars($widgetClass);

        if (! isset($vars[$property]) || ! is_int($vars[$property]) || $vars[$property] < 1) {
            return $fallback;
        }

        return min($max, $vars[$property]);
    }

    /**
     * @param  class-string  $widgetClass
     * @param  list<class-string>  $parents
     */
    private static function isSubclassOfAny(string $widgetClass, array $parents): bool
    {
        if (! class_exists($widgetClass)) {
            return false;
        }

        foreach ($parents as $parent) {
            if (class_exists($parent) && is_subclass_of($widgetClass, $parent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string  $widgetClass
     */
    private static function filamentColumnSpan(string $widgetClass): int
    {
        if (! class_exists($widgetClass)) {
            return 4;
        }

        try {
            $widget = app($widgetClass);
        } catch (Throwable) {
            return 4;
        }

        if (! method_exists($widget, 'getColumnSpan')) {
            return 4;
        }

        try {
            $span = $widget->getColumnSpan();
        } catch (Throwable) {
            return 4;
        }

        if ($span === 'full') {
            return 12;
        }

        if (is_array($span)) {
            $span = $span['lg'] ?? $span['md'] ?? $span['default'] ?? reset($span);
        }

        if ($span === 'full') {
            return 12;
        }

        if (is_numeric($span) && (int) $span > 0) {
            return min(12, (int) $span);
        }

        return 4;
    }
}
