<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $panel_id
 * @property string $key
 * @property string|null $value
 */
class WidgetGridSetting extends Model
{
    protected $table = 'widget_grid_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'panel_id',
        'key',
        'value',
    ];

    public static function getValue(string $panelId, string $key, mixed $default = null): mixed
    {
        $cacheKey = self::cacheKey($panelId, $key);

        return Cache::remember($cacheKey, 60, function () use ($panelId, $key, $default) {
            $row = static::query()
                ->where('panel_id', $panelId)
                ->where('key', $key)
                ->first();

            if ($row === null) {
                return $default;
            }

            return $row->value;
        });
    }

    public static function setValue(string $panelId, string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            [
                'panel_id' => $panelId,
                'key' => $key,
            ],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            ]
        );

        Cache::forget(self::cacheKey($panelId, $key));
    }

    public static function isLocked(string $panelId): bool
    {
        return (bool) (int) self::getValue($panelId, 'customization_locked', '0');
    }

    public static function setLocked(string $panelId, bool $locked): void
    {
        self::setValue($panelId, 'customization_locked', $locked);
    }

    protected static function cacheKey(string $panelId, string $key): string
    {
        return "filament-widget-grid.{$panelId}.{$key}";
    }
}
