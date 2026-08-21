<?php

declare(strict_types=1);

namespace JohnRivera7\FilamentWidgetGrid\Concerns;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use JohnRivera7\FilamentWidgetGrid\FilamentWidgetGridPlugin;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridLayout;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridSetting;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridTemplate;
use JohnRivera7\FilamentWidgetGrid\Support\LayoutPacker;
use JohnRivera7\FilamentWidgetGrid\Support\WidgetInspector;
use Livewire\Attributes\Url;

trait HasWidgetGrid
{
    public bool $widgetGridEditing = false;

    public string $widgetGridTemplateName = '';

    public ?int $widgetGridLoadingTemplateId = null;

    /**
     * @var array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    public array $widgetGridLayout = [];

    #[Url(as: 'customize')]
    public bool $openCustomize = false;

    public function mountHasWidgetGrid(): void
    {
        $this->widgetGridLayout = $this->resolveWidgetGridLayout();

        if ($this->openCustomize && $this->canCustomizeWidgetGrid()) {
            $this->widgetGridEditing = true;
        }
    }

    public function getWidgetsContentComponent(): Component
    {
        return View::make('filament-widget-grid::grid');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ...$this->getWidgetGridHeaderActions(),
            ...$this->getAdditionalHeaderActions(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getAdditionalHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<Action>
     */
    protected function getWidgetGridHeaderActions(): array
    {
        return [
            Action::make('widgetGridSave')
                ->label(__('filament-widget-grid::widget-grid.save'))
                ->icon(Heroicon::Check)
                ->color('success')
                ->visible(fn (): bool => $this->widgetGridEditing && $this->canCustomizeWidgetGrid())
                ->action('saveWidgetGridLayout'),
            Action::make('widgetGridCancel')
                ->label(__('filament-widget-grid::widget-grid.cancel'))
                ->icon(Heroicon::XMark)
                ->color('gray')
                ->visible(fn (): bool => $this->widgetGridEditing)
                ->action('cancelWidgetGridEditing'),
            Action::make('widgetGridReset')
                ->label(__('filament-widget-grid::widget-grid.reset'))
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('filament-widget-grid::widget-grid.reset'))
                ->modalDescription(__('filament-widget-grid::widget-grid.reset_help'))
                ->visible(fn (): bool => $this->widgetGridEditing && $this->canCustomizeWidgetGrid() && $this->userHasPersonalWidgetGridLayout())
                ->action('resetWidgetGridToDefault'),
            Action::make('widgetGridSaveDefault')
                ->label(__('filament-widget-grid::widget-grid.save_default'))
                ->icon(Heroicon::GlobeAlt)
                ->color('warning')
                ->visible(fn (): bool => $this->widgetGridEditing && $this->canManageWidgetGridDefaults())
                ->action('saveWidgetGridAsDefault'),
            Action::make('widgetGridApplyAll')
                ->label(__('filament-widget-grid::widget-grid.apply_all'))
                ->icon(Heroicon::Users)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->widgetGridEditing && $this->canManageWidgetGridDefaults())
                ->action('applyWidgetGridDefaultToAll'),
        ];
    }

    /**
     * Livewire may call this with a nested scalar (e.g. a single `w`) when a cell is resized,
     * or with [] when GridStack fires `change` during a re-render. Never wipe a populated layout.
     */
    public function updatedWidgetGridLayout(mixed $value, mixed $key = null): void
    {
        if (is_string($key) && $key !== '') {
            return;
        }

        $items = $this->extractWidgetGridItems($value) ?? $this->extractWidgetGridItems($this->widgetGridLayout);

        if ($items === null || $items === []) {
            return;
        }

        $this->widgetGridLayout = $this->sanitizeWidgetGridItems($items);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function extractWidgetGridItems(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        if (isset($value['items']) && is_array($value['items'])) {
            $value = $value['items'];
        }

        $first = $value[array_key_first($value)] ?? null;

        if (! is_array($first) || ! isset($first['widget'])) {
            return null;
        }

        return $value;
    }

    /**
     * @param  mixed  $value
     */
    protected function isWidgetGridLayoutPayload(mixed $value): bool
    {
        return $this->extractWidgetGridItems($value) !== null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncWidgetGridLayout(array $items): void
    {
        if (! $this->canCustomizeWidgetGrid() || $items === []) {
            return;
        }

        $this->widgetGridLayout = $this->sanitizeWidgetGridItems($items);
    }

    public function saveWidgetGridLayout(): void
    {
        if (! $this->canCustomizeWidgetGrid()) {
            return;
        }

        $userId = $this->widgetGridUserId();

        if ($userId === null) {
            return;
        }

        WidgetGridLayout::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'panel_id' => $this->widgetGridPanelId(),
                'is_default' => false,
            ],
            [
                'items' => $this->storedWidgetGridPayload(),
            ]
        );

        $this->widgetGridEditing = false;
        $this->openCustomize = false;

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.saved'))
            ->send();
    }

    public function cancelWidgetGridEditing(): void
    {
        $this->widgetGridEditing = false;
        $this->openCustomize = false;
        $this->widgetGridLayout = $this->resolveWidgetGridLayout();
    }

    public function resetWidgetGridToDefault(): void
    {
        if (! $this->canCustomizeWidgetGrid()) {
            return;
        }

        $userId = $this->widgetGridUserId();

        if ($userId === null) {
            return;
        }

        WidgetGridLayout::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->where('is_default', false)
            ->delete();

        $this->widgetGridLayout = $this->resolveWidgetGridLayout();

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.reset_done'))
            ->send();
    }

    public function userHasPersonalWidgetGridLayout(): bool
    {
        $userId = $this->widgetGridUserId();

        if ($userId === null || ! $this->widgetGridTablesReady()) {
            return false;
        }

        return WidgetGridLayout::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->where('is_default', false)
            ->exists();
    }

    public function saveWidgetGridAsDefault(): void
    {
        if (! $this->canManageWidgetGridDefaults()) {
            return;
        }

        WidgetGridLayout::query()->updateOrCreate(
            [
                'user_id' => null,
                'panel_id' => $this->widgetGridPanelId(),
                'is_default' => true,
            ],
            [
                'items' => $this->storedWidgetGridPayload(),
            ]
        );

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.default_saved'))
            ->send();
    }

    public function applyWidgetGridDefaultToAll(): void
    {
        if (! $this->canManageWidgetGridDefaults()) {
            return;
        }

        $default = $this->defaultWidgetGridRecord();

        if ($default === null) {
            Notification::make()
                ->warning()
                ->title(__('filament-widget-grid::widget-grid.no_default'))
                ->send();

            return;
        }

        $userModel = config('auth.providers.users.model');
        $userIds = $userModel::query()->pluck('id');

        foreach ($userIds as $userId) {
            WidgetGridLayout::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'panel_id' => $this->widgetGridPanelId(),
                    'is_default' => false,
                ],
                [
                    'items' => $default->items ?? [],
                ]
            );
        }

        $this->widgetGridLayout = $this->resolveWidgetGridLayout();

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.applied_all'))
            ->send();
    }

    public function addWidgetToGrid(string $widgetClass): void
    {
        if (! $this->canCustomizeWidgetGrid() || ! $this->canViewWidgetOnGrid($widgetClass)) {
            return;
        }

        if ($this->isWidgetOnGrid($widgetClass)) {
            return;
        }

        $plugin = $this->widgetGridPlugin();
        $columns = $plugin->getColumns();
        $this->widgetGridLayout[] = [
            'widget' => $widgetClass,
            'x' => -1,
            'y' => -1,
            'w' => WidgetInspector::defaultWidth($widgetClass, $columns),
            'h' => WidgetInspector::defaultHeight($widgetClass, $plugin->getCellHeight(), $plugin->getMaxHeight()),
            'visible' => true,
        ];
        $this->widgetGridLayout = LayoutPacker::compact($this->widgetGridLayout, $columns);
    }

    public function removeWidgetFromGrid(string $widgetClass): void
    {
        if (! $this->canCustomizeWidgetGrid()) {
            return;
        }

        $this->widgetGridLayout = array_values(array_filter(
            $this->widgetGridLayout,
            fn (array $item): bool => $item['widget'] !== $widgetClass
        ));
    }

    public function toggleWidgetOnGrid(string $widgetClass, bool $visible): void
    {
        if ($visible) {
            $this->addWidgetToGrid($widgetClass);

            return;
        }

        $this->removeWidgetFromGrid($widgetClass);
    }

    public function saveWidgetGridTemplateFromInput(): void
    {
        $this->saveWidgetGridTemplate($this->widgetGridTemplateName);
    }

    public function saveWidgetGridTemplate(string $name): void
    {
        if (! $this->canCustomizeWidgetGrid() || ! $this->widgetGridPlugin()->hasTemplates()) {
            return;
        }

        $userId = $this->widgetGridUserId();
        $name = trim($name);

        if ($userId === null || $name === '') {
            Notification::make()
                ->warning()
                ->title(__('filament-widget-grid::widget-grid.template_name_required'))
                ->send();

            return;
        }

        WidgetGridTemplate::query()->create([
            'user_id' => $userId,
            'panel_id' => $this->widgetGridPanelId(),
            'name' => $name,
            'items' => $this->storedWidgetGridPayload(),
            'is_shared' => false,
        ]);

        $this->widgetGridTemplateName = '';

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.template_saved'))
            ->send();
    }

    public function restoreWidgetGridTemplate(int $id): void
    {
        if (! $this->canCustomizeWidgetGrid()) {
            return;
        }

        $userId = $this->widgetGridUserId();
        $template = WidgetGridTemplate::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->find($id);

        if ($template === null || ! is_array($template->items)) {
            Notification::make()
                ->danger()
                ->title(__('filament-widget-grid::widget-grid.template_missing'))
                ->send();

            return;
        }

        $this->applyWidgetGridItems($template->items);
    }

    public function shareWidgetGridTemplate(int $id): void
    {
        $this->setWidgetGridTemplateShared($id, true);
    }

    public function unshareWidgetGridTemplate(int $id): void
    {
        $this->setWidgetGridTemplateShared($id, false);
    }

    public function deleteWidgetGridTemplate(int $id): void
    {
        if (! $this->canCustomizeWidgetGrid() || ! $this->widgetGridPlugin()->hasTemplates()) {
            return;
        }

        $userId = $this->widgetGridUserId();

        if ($userId === null) {
            return;
        }

        $template = WidgetGridTemplate::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->find($id);

        if ($template === null) {
            Notification::make()
                ->danger()
                ->title(__('filament-widget-grid::widget-grid.template_missing'))
                ->send();

            return;
        }

        $template->delete();

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.template_deleted'))
            ->send();
    }

    public function applySharedWidgetGridTemplate(int $id): void
    {
        if (! $this->canCustomizeWidgetGrid()) {
            return;
        }

        $template = WidgetGridTemplate::query()
            ->where('is_shared', true)
            ->where('panel_id', $this->widgetGridPanelId())
            ->find($id);

        if ($template === null || ! is_array($template->items)) {
            Notification::make()
                ->danger()
                ->title(__('filament-widget-grid::widget-grid.template_missing'))
                ->send();

            return;
        }

        $this->applyWidgetGridItems($template->items);
    }

    /**
     * @return Collection<int, WidgetGridTemplate>
     */
    public function getMyWidgetGridTemplates(): Collection
    {
        $userId = $this->widgetGridUserId();

        if ($userId === null || ! $this->widgetGridTablesReady()) {
            return new Collection;
        }

        return WidgetGridTemplate::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return Collection<int, WidgetGridTemplate>
     */
    public function getSharedWidgetGridTemplates(): Collection
    {
        $userId = $this->widgetGridUserId();

        if ($userId === null || ! $this->widgetGridTablesReady()) {
            return new Collection;
        }

        return WidgetGridTemplate::query()
            ->with('user')
            ->where('is_shared', true)
            ->where('panel_id', $this->widgetGridPanelId())
            ->where('user_id', '!=', $userId)
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return array<int, array{class: string, title: string, visible: bool}>
     */
    public function getWidgetGridCatalog(): array
    {
        return collect($this->getWidgets())
            ->map(fn (string | WidgetConfiguration $widget): string => FilamentWidgetGridPlugin::widgetClass($widget))
            ->unique()
            ->filter(fn (string $class): bool => $this->canViewWidgetOnGrid($class))
            ->map(fn (string $class): array => [
                'class' => $class,
                'title' => WidgetInspector::title($class),
                'visible' => $this->isWidgetOnGrid($class),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    public function getVisibleWidgetGridLayout(): array
    {
        $items = $this->extractWidgetGridItems($this->widgetGridLayout) ?? [];

        return array_values(array_filter(
            $items,
            fn (mixed $item): bool => is_array($item)
                && ($item['visible'] ?? false)
                && isset($item['widget'])
                && is_string($item['widget'])
                && $this->canViewWidgetOnGrid($item['widget'])
        ));
    }

    public function canCustomizeWidgetGrid(): bool
    {
        if ($this->isWidgetGridLocked() && ! $this->canManageWidgetGridDefaults()) {
            return false;
        }

        return $this->widgetGridPlugin()->userCanCustomize();
    }

    public function canManageWidgetGridDefaults(): bool
    {
        return $this->widgetGridPlugin()->userCanManageDefaults();
    }

    public function isWidgetGridLocked(): bool
    {
        if (! $this->widgetGridTablesReady()) {
            return false;
        }

        return WidgetGridSetting::isLocked($this->widgetGridPanelId());
    }

    public function widgetGridPanelId(): string
    {
        return Filament::getCurrentPanel()?->getId() ?? 'default';
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetGridAlpineConfig(): array
    {
        $plugin = $this->widgetGridPlugin();

        return [
            'editable' => $this->widgetGridEditing && $this->canCustomizeWidgetGrid(),
            'columns' => $plugin->getColumns(),
            'cellHeight' => $plugin->getCellHeight(),
            'float' => $plugin->shouldFloat(),
            'margin' => 8,
            'minW' => 1,
            'minH' => 1,
            'items' => $this->getVisibleWidgetGridLayout(),
        ];
    }

    /**
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    protected function resolveWidgetGridLayout(): array
    {
        $allowed = $this->allowedWidgetClasses();

        if (! $this->widgetGridTablesReady()) {
            return $this->defaultPackedLayout($allowed);
        }

        if ($this->isWidgetGridLocked()) {
            $default = $this->defaultWidgetGridRecord();

            if ($default === null) {
                return $this->defaultPackedLayout($allowed);
            }

            return $this->itemsFromStorage($default->items ?? [], $allowed);
        }

        $userId = $this->widgetGridUserId();
        $userLayout = $userId === null ? null : WidgetGridLayout::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->where('is_default', false)
            ->first();

        if ($userLayout !== null && is_array($userLayout->items) && $userLayout->items !== []) {
            return $this->itemsFromStorage($userLayout->items, $allowed);
        }

        $default = $this->defaultWidgetGridRecord();

        if ($default !== null && is_array($default->items) && $default->items !== []) {
            return $this->itemsFromStorage($default->items, $allowed);
        }

        return $this->defaultPackedLayout($allowed);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>|null  $allowed
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    protected function sanitizeWidgetGridItems(array $items, ?array $allowed = null): array
    {
        $plugin = $this->widgetGridPlugin();
        $allowed ??= $this->allowedWidgetClasses();
        $normalized = LayoutPacker::normalize($items, $plugin->getColumns(), $plugin->getMaxHeight());

        foreach ($normalized as $index => $item) {
            $minW = WidgetInspector::gridMinWidth($item['widget'], $plugin->getColumns());
            $minH = WidgetInspector::gridMinHeight($item['widget'], $plugin->getMaxHeight());
            $item['w'] = max($item['w'], $minW);
            $item['h'] = max($item['h'], $minH);
            $item['x'] = max(0, min($item['x'], $plugin->getColumns() - $item['w']));
            $normalized[$index] = $item;
        }

        $normalized = LayoutPacker::compact($normalized, $plugin->getColumns());

        if ($allowed === []) {
            return array_values($normalized);
        }

        return array_values(array_filter(
            $normalized,
            fn (array $item): bool => in_array($item['widget'], $allowed, true)
        ));
    }

    /**
     * @param  array<int, string>|null  $allowed
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    protected function itemsFromStorage(mixed $stored, ?array $allowed = null): array
    {
        $plugin = $this->widgetGridPlugin();
        $unwrapped = LayoutPacker::unwrapFromStorage($stored);
        $scaled = LayoutPacker::scale(
            $unwrapped['items'],
            $unwrapped['columns'],
            $plugin->getColumns(),
            $unwrapped['cellHeight'],
            $plugin->getCellHeight(),
        );

        return $this->sanitizeWidgetGridItems(
            array_map(
                fn (array $item): array => WidgetInspector::inflateLegacySize(
                    $item,
                    $plugin->getColumns(),
                    $plugin->getCellHeight(),
                    $plugin->getMaxHeight(),
                ),
                $scaled,
            ),
            $allowed,
        );
    }

    /**
     * @return array{columns: int, cellHeight: int, items: array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>}
     */
    protected function storedWidgetGridPayload(): array
    {
        $plugin = $this->widgetGridPlugin();

        return LayoutPacker::wrapForStorage(
            $this->visibleWidgetGridItems(),
            $plugin->getColumns(),
            $plugin->getCellHeight(),
        );
    }

    /**
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    protected function visibleWidgetGridItems(): array
    {
        return array_values(array_filter(
            $this->sanitizeWidgetGridItems($this->widgetGridLayout),
            fn (array $item): bool => $item['visible']
        ));
    }

    /**
     * @param  array<string, mixed>  $items
     */
    protected function applyWidgetGridItems(array $items): void
    {
        $this->widgetGridLayout = $this->itemsFromStorage($items);

        $userId = $this->widgetGridUserId();

        if ($userId !== null) {
            WidgetGridLayout::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'panel_id' => $this->widgetGridPanelId(),
                    'is_default' => false,
                ],
                [
                    'items' => $this->storedWidgetGridPayload(),
                ]
            );
        }

        Notification::make()
            ->success()
            ->title(__('filament-widget-grid::widget-grid.template_applied'))
            ->send();
    }

    protected function setWidgetGridTemplateShared(int $id, bool $shared): void
    {
        if (! $this->widgetGridPlugin()->userCanShareTemplates()) {
            return;
        }

        $this->widgetGridLoadingTemplateId = $id;
        $userId = $this->widgetGridUserId();
        $template = WidgetGridTemplate::query()
            ->where('user_id', $userId)
            ->where('panel_id', $this->widgetGridPanelId())
            ->find($id);

        if ($template === null) {
            $this->widgetGridLoadingTemplateId = null;
            Notification::make()
                ->danger()
                ->title(__('filament-widget-grid::widget-grid.template_missing'))
                ->send();

            return;
        }

        $template->update(['is_shared' => $shared]);
        $this->widgetGridLoadingTemplateId = null;

        Notification::make()
            ->success()
            ->title($shared
                ? __('filament-widget-grid::widget-grid.template_shared')
                : __('filament-widget-grid::widget-grid.template_unshared'))
            ->send();
    }

    protected function canViewWidgetOnGrid(string $widgetClass): bool
    {
        return $this->widgetGridPlugin()->userCanViewWidget($widgetClass);
    }

    protected function isWidgetOnGrid(string $widgetClass): bool
    {
        foreach ($this->widgetGridLayout as $item) {
            if ($item['widget'] === $widgetClass && $item['visible']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function allowedWidgetClasses(): array
    {
        return collect($this->getWidgets())
            ->map(fn (string | WidgetConfiguration $widget): string => FilamentWidgetGridPlugin::widgetClass($widget))
            ->unique()
            ->filter(fn (string $class): bool => $this->canViewWidgetOnGrid($class))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $allowed
     * @return array<int, array{widget: string, x: int, y: int, w: int, h: int, visible: bool}>
     */
    protected function defaultPackedLayout(array $allowed): array
    {
        $plugin = $this->widgetGridPlugin();
        $items = [];

        foreach ($allowed as $class) {
            $items[] = [
                'widget' => $class,
                'x' => -1,
                'y' => -1,
                'w' => WidgetInspector::defaultWidth($class, $plugin->getColumns()),
                'h' => WidgetInspector::defaultHeight($class, $plugin->getCellHeight(), $plugin->getMaxHeight()),
                'visible' => true,
            ];
        }

        return LayoutPacker::compact($items, $plugin->getColumns());
    }

    protected function defaultWidgetGridRecord(): ?WidgetGridLayout
    {
        return WidgetGridLayout::query()
            ->whereNull('user_id')
            ->where('panel_id', $this->widgetGridPanelId())
            ->where('is_default', true)
            ->first();
    }

    public function widgetGridUserId(): int | string | null
    {
        return $this->widgetGridPlugin()->resolveUserId();
    }

    public function widgetGridPlugin(): FilamentWidgetGridPlugin
    {
        return FilamentWidgetGridPlugin::get();
    }

    protected function widgetGridTablesReady(): bool
    {
        return Schema::hasTable('widget_grid_layouts');
    }
}
