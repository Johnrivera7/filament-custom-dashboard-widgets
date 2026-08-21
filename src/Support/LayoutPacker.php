<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Support;

final class LayoutPacker
{
    public const LEGACY_COLUMNS = 12;

    public const LEGACY_CELL_HEIGHT = 180;

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    public static function normalize(array $items, int $columns = 24, int $maxHeight = 60): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $widget = $item['widget'] ?? $item['widget_name'] ?? null;

            if (! is_string($widget) || $widget === '') {
                continue;
            }

            $w = max(1, min($columns, (int) ($item['w'] ?? $item['column_span'] ?? max(1, (int) round(4 * $columns / 12)))));
            $h = max(1, min($maxHeight, (int) ($item['h'] ?? $item['row_span'] ?? 4)));
            $hasExplicitPosition = array_key_exists('x', $item) || array_key_exists('grid_column_start', $item);

            $normalized[] = [
                'widget' => $widget,
                'x' => $hasExplicitPosition
                    ? max(0, min($columns - $w, self::readX($item)))
                    : -1,
                'y' => $hasExplicitPosition
                    ? max(0, self::readY($item))
                    : -1,
                'w' => $w,
                'h' => $h,
                'visible' => (bool) ($item['visible'] ?? $item['show_widget'] ?? true),
            ];
        }

        return self::compact($normalized, $columns);
    }

    /**
     * @param  array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>  $items
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    public static function compact(array $items, int $columns = 24): array
    {
        $occupied = [];
        $packed = [];

        foreach ($items as $item) {
            if ($item['x'] >= 0 && $item['y'] >= 0 && self::fits($occupied, $item['x'], $item['y'], $item['w'], $item['h'], $columns)) {
                self::mark($occupied, $item['x'], $item['y'], $item['w'], $item['h']);
                $packed[] = $item;

                continue;
            }

            $placed = self::firstFit($occupied, $item['w'], $item['h'], $columns);
            $item['x'] = $placed['x'];
            $item['y'] = $placed['y'];
            self::mark($occupied, $item['x'], $item['y'], $item['w'], $item['h']);
            $packed[] = $item;
        }

        return array_values($packed);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{widget: string, x: int, y: int, w: int, h: int, visible: bool}
     */
    public static function fromDashArrange(array $row, int $columns = 12, int $maxHeight = 8): array
    {
        $normalized = self::normalize([$row], $columns, $maxHeight);

        return $normalized[0];
    }

    /**
     * @param  array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>  $items
     * @return array{columns: int, cellHeight: int, items: array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>}
     */
    public static function wrapForStorage(array $items, int $columns, int $cellHeight): array
    {
        return [
            'columns' => $columns,
            'cellHeight' => $cellHeight,
            'items' => array_values($items),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, columns: int, cellHeight: int}
     */
    public static function unwrapFromStorage(mixed $stored, int $fallbackColumns = self::LEGACY_COLUMNS, int $fallbackCellHeight = self::LEGACY_CELL_HEIGHT): array
    {
        if (! is_array($stored) || $stored === []) {
            return [
                'items' => [],
                'columns' => $fallbackColumns,
                'cellHeight' => $fallbackCellHeight,
            ];
        }

        if (isset($stored['items']) && is_array($stored['items'])) {
            return [
                'items' => array_values($stored['items']),
                'columns' => max(1, (int) ($stored['columns'] ?? $fallbackColumns)),
                'cellHeight' => max(1, (int) ($stored['cellHeight'] ?? $fallbackCellHeight)),
            ];
        }

        return [
            'items' => array_values($stored),
            'columns' => $fallbackColumns,
            'cellHeight' => $fallbackCellHeight,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function scale(array $items, int $fromColumns, int $toColumns, int $fromCellHeight, int $toCellHeight): array
    {
        if ($fromColumns === $toColumns && $fromCellHeight === $toCellHeight) {
            return $items;
        }

        $xFactor = $toColumns / max(1, $fromColumns);
        $yFactor = $fromCellHeight / max(1, $toCellHeight);
        $scaled = [];

        foreach ($items as $item) {
            $item['x'] = (int) round((int) ($item['x'] ?? 0) * $xFactor);
            $item['y'] = (int) round((int) ($item['y'] ?? 0) * $yFactor);
            $item['w'] = max(1, (int) round((int) ($item['w'] ?? 1) * $xFactor));
            $item['h'] = max(1, (int) round((int) ($item['h'] ?? 1) * $yFactor));
            $scaled[] = $item;
        }

        return $scaled;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function readX(array $item): int
    {
        if (array_key_exists('x', $item) && $item['x'] !== null) {
            return (int) $item['x'];
        }

        if (isset($item['grid_column_start'])) {
            return (int) $item['grid_column_start'] - 1;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function readY(array $item): int
    {
        if (array_key_exists('y', $item) && $item['y'] !== null) {
            return (int) $item['y'];
        }

        if (isset($item['grid_row_start'])) {
            return (int) $item['grid_row_start'] - 1;
        }

        return 0;
    }

    /**
     * @param  array<int, array<int, bool>>  $occupied
     * @return array{x: int, y: int}
     */
    private static function firstFit(array $occupied, int $w, int $h, int $columns): array
    {
        for ($y = 0; $y < 500; $y++) {
            for ($x = 0; $x <= $columns - $w; $x++) {
                if (self::fits($occupied, $x, $y, $w, $h, $columns)) {
                    return ['x' => $x, 'y' => $y];
                }
            }
        }

        return ['x' => 0, 'y' => 0];
    }

    /**
     * @param  array<int, array<int, bool>>  $occupied
     */
    private static function fits(array $occupied, int $x, int $y, int $w, int $h, int $columns): bool
    {
        if ($x < 0 || $y < 0 || $x + $w > $columns) {
            return false;
        }

        for ($row = $y; $row < $y + $h; $row++) {
            for ($col = $x; $col < $x + $w; $col++) {
                if (isset($occupied[$row][$col])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<int, array<int, bool>>  $occupied
     */
    private static function mark(array &$occupied, int $x, int $y, int $w, int $h): void
    {
        for ($row = $y; $row < $y + $h; $row++) {
            for ($col = $x; $col < $x + $w; $col++) {
                $occupied[$row][$col] = true;
            }
        }
    }
}
