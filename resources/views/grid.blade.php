@php
    $page = isset($schemaComponent) ? $schemaComponent->getLivewire() : $this;
    $config = $page->getWidgetGridAlpineConfig();
    $catalog = $page->widgetGridEditing ? $page->getWidgetGridCatalog() : [];
    $visible = $page->getVisibleWidgetGridLayout();
@endphp

{{-- Stub until x-load registers the real Alpine component. --}}
<div x-data="{ filamentWidgetGrid() {} }">
<div
    class="fi-wg"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-widget-grid-styles', 'johnrivera7/filament-custom-dashboard-widgets'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filamentWidgetGrid', 'johnrivera7/filament-custom-dashboard-widgets') }}"
    x-data="filamentWidgetGrid(@js($config))"
    x-on:keydown.escape.window="onEscape($event)"
>
    @if ($page->widgetGridEditing && $page->canCustomizeWidgetGrid())
        <div class="fi-wg-toolbar">
            <p class="fi-wg-mobile-note">{{ __('filament-widget-grid::widget-grid.mobile_note') }}</p>
            <div class="fi-wg-panel">
                <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.available_widgets') }}</h3>
                <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.available_widgets_help') }}</p>
                <input
                    type="search"
                    x-model="catalogQuery"
                    placeholder="{{ __('filament-widget-grid::widget-grid.search_widgets') }}"
                    class="fi-input fi-wg-input fi-wg-search"
                    autocomplete="off"
                />
                <div class="fi-wg-catalog">
                    @foreach ($catalog as $widget)
                        <label
                            class="fi-wg-catalog-item"
                            wire:key="wg-catalog-{{ md5($widget['class']) }}"
                            x-show="matchesCatalog(@js($widget['title']))"
                            x-cloak
                        >
                            <input
                                type="checkbox"
                                class="fi-wg-catalog-checkbox"
                                @checked($widget['visible'])
                                x-on:change="toggleWidget(@js($widget['class']), $event.target.checked)"
                            />
                            <span>{{ $widget['title'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            @if ($page->widgetGridPlugin()->hasTemplates())
                <div class="fi-wg-panel">
                    <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.templates') }}</h3>
                    <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.templates_help') }}</p>
                    <div class="fi-wg-template-form">
                        <input
                            type="text"
                            wire:model="widgetGridTemplateName"
                            placeholder="{{ __('filament-widget-grid::widget-grid.template_name') }}"
                            class="fi-input fi-wg-input"
                        />
                        <x-filament::button size="sm" x-on:click.prevent="saveAsTemplate()">
                            {{ __('filament-widget-grid::widget-grid.save_template') }}
                        </x-filament::button>
                    </div>
                    @php $myTemplates = $page->getMyWidgetGridTemplates(); @endphp
                    @if ($myTemplates->isNotEmpty())
                        <ul class="fi-wg-template-list">
                            @foreach ($myTemplates as $template)
                                <li class="fi-wg-template-row" wire:key="wg-my-template-{{ $template->id }}">
                                    <span>{{ $template->name }}</span>
                                    <div class="fi-wg-template-actions">
                                        @if ($page->widgetGridPlugin()->userCanShareTemplates())
                                            @if ($template->is_shared)
                                                <x-filament::button size="xs" color="warning" wire:click="unshareWidgetGridTemplate({{ $template->id }})" :disabled="$page->widgetGridLoadingTemplateId === $template->id">
                                                    {{ __('filament-widget-grid::widget-grid.unshare') }}
                                                </x-filament::button>
                                            @else
                                                <x-filament::button size="xs" color="success" wire:click="shareWidgetGridTemplate({{ $template->id }})" :disabled="$page->widgetGridLoadingTemplateId === $template->id">
                                                    {{ __('filament-widget-grid::widget-grid.share') }}
                                                </x-filament::button>
                                            @endif
                                        @endif
                                        <x-filament::button size="xs" color="gray" wire:click="restoreWidgetGridTemplate({{ $template->id }})">
                                            {{ __('filament-widget-grid::widget-grid.restore') }}
                                        </x-filament::button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.no_templates') }}</p>
                    @endif
                </div>

                <div class="fi-wg-panel">
                    <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.shared_templates') }}</h3>
                    <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.shared_templates_help') }}</p>
                    @php $sharedTemplates = $page->getSharedWidgetGridTemplates(); @endphp
                    @if ($sharedTemplates->isNotEmpty())
                        <ul class="fi-wg-template-list">
                            @foreach ($sharedTemplates as $template)
                                <li class="fi-wg-template-row" wire:key="wg-shared-template-{{ $template->id }}">
                                    <div>
                                        <span>{{ $template->name }}</span>
                                        <span class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.by') }} {{ $template->user->name ?? __('filament-widget-grid::widget-grid.by') }}</span>
                                    </div>
                                    <x-filament::button size="xs" wire:click="applySharedWidgetGridTemplate({{ $template->id }})">
                                        {{ __('filament-widget-grid::widget-grid.apply') }}
                                    </x-filament::button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.no_shared_templates') }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div
        x-ref="grid"
        class="grid-stack fi-wg-grid"
        wire:key="fi-wg-stack"
        @class(['fi-wg-grid-editing' => $page->widgetGridEditing])
    >
        @forelse ($visible as $item)
            <div
                class="grid-stack-item fi-wg-item"
                gs-id="{{ $item['widget'] }}"
                gs-x="{{ $item['x'] }}"
                gs-y="{{ $item['y'] }}"
                gs-w="{{ $item['w'] }}"
                gs-h="{{ $item['h'] }}"
                gs-min-w="{{ \JohnRivera7\FilamentWidgetGrid\Support\WidgetInspector::gridMinWidth($item['widget'], $page->widgetGridPlugin()->getColumns()) }}"
                gs-min-h="{{ \JohnRivera7\FilamentWidgetGrid\Support\WidgetInspector::gridMinHeight($item['widget'], $page->widgetGridPlugin()->getMaxHeight()) }}"
                @if (\JohnRivera7\FilamentWidgetGrid\Support\WidgetInspector::sizeToContent($item['widget']))
                    gs-size-to-content="true"
                @endif
                wire:key="wg-item-{{ md5($item['widget']) }}"
            >
                <div class="grid-stack-item-content fi-wg-item-content">
                    @if ($page->widgetGridEditing)
                        <button
                            type="button"
                            class="fi-wg-remove"
                            title="{{ __('filament-widget-grid::widget-grid.remove_widget') }}"
                            x-on:click.stop="toggleWidget(@js($item['widget']), false)"
                        >
                            ×
                        </button>
                    @endif
                    <div class="fi-wg-widget" @class(['fi-wg-widget-locked' => $page->widgetGridEditing])>
                        @livewire($item['widget'], key('wg-' . $item['widget'] . '-' . ($page->widgetGridUserId() ?? 'guest')))
                    </div>
                </div>
            </div>
        @empty
            <p class="fi-wg-empty">{{ __('filament-widget-grid::widget-grid.empty') }}</p>
        @endforelse
    </div>
</div>
</div>
