<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridLayout;
use JohnRivera7\FilamentWidgetGrid\Support\LayoutPacker;

class ImportDashArrangeCommand extends Command
{
    public $signature = 'filament-widget-grid:import-dash-arrange
        {--panel=admin : Filament panel id}
        {--dry-run : Show what would be imported without writing}';

    public $description = 'Import layouts from shreejan/dash-arrange tables into Widget Grid';

    public function handle(): int
    {
        if (! Schema::hasTable('user_widget_preferences')) {
            $this->error('Table user_widget_preferences was not found.');

            return self::FAILURE;
        }

        $panelId = (string) $this->option('panel');
        $dryRun = (bool) $this->option('dry-run');
        $grouped = DB::table('user_widget_preferences')
            ->orderBy('user_id')
            ->orderBy('order')
            ->get()
            ->groupBy('user_id');

        $imported = 0;

        foreach ($grouped as $userId => $rows) {
            $items = LayoutPacker::normalize(
                $rows->map(fn (object $row): array => (array) $row)->all(),
                LayoutPacker::LEGACY_COLUMNS,
                8,
            );

            if ($dryRun) {
                $this->line("User {$userId}: " . count($items) . ' widgets');
                $imported++;

                continue;
            }

            WidgetGridLayout::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'panel_id' => $panelId,
                    'is_default' => false,
                ],
                [
                    'items' => LayoutPacker::wrapForStorage($items, LayoutPacker::LEGACY_COLUMNS, LayoutPacker::LEGACY_CELL_HEIGHT),
                ]
            );
            $imported++;
        }

        if (Schema::hasTable('dashboard_default_layout')) {
            $defaults = DB::table('dashboard_default_layout')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();

            if ($defaults !== []) {
                $items = LayoutPacker::normalize($defaults, LayoutPacker::LEGACY_COLUMNS, 8);

                if (! $dryRun) {
                    WidgetGridLayout::query()->updateOrCreate(
                        [
                            'user_id' => null,
                            'panel_id' => $panelId,
                            'is_default' => true,
                        ],
                        [
                            'items' => LayoutPacker::wrapForStorage($items, LayoutPacker::LEGACY_COLUMNS, LayoutPacker::LEGACY_CELL_HEIGHT),
                        ]
                    );
                }

                $this->info('Imported default layout (' . count($items) . ' widgets).');
            }
        }

        $this->info($dryRun
            ? "Dry run: {$imported} user layouts would be imported."
            : "Imported {$imported} user layouts.");

        return self::SUCCESS;
    }
}
