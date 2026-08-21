<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $user_id
 * @property string $panel_id
 * @property bool $is_default
 * @property array<int, array<string, mixed>>|null $items
 */
class WidgetGridLayout extends Model
{
    protected $table = 'widget_grid_layouts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'panel_id',
        'items',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }
}
