<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property string $panel_id
 * @property string $name
 * @property array<int, array<string, mixed>>|null $items
 * @property bool $is_shared
 */
class WidgetGridTemplate extends Model
{
    protected $table = 'widget_grid_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'panel_id',
        'name',
        'items',
        'is_shared',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }
}
