<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class Dashboard extends BaseDashboard
{
    use HasWidgetGrid;
}
