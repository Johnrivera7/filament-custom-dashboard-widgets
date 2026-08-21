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

            <section
                class="fi-wg-panel fi-wg-panel--catalog"
                :class="{ 'fi-wg-panel--open': isToolbarPanelOpen('catalog') }"
            >
                <button
                    type="button"
                    class="fi-wg-accordion-trigger"
                    x-on:click="toggleToolbarPanel('catalog')"
                    :aria-expanded="isToolbarPanelOpen('catalog').toString()"
                >
                    <div class="fi-wg-panel-heading">
                        <span class="fi-wg-panel-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                                <path d="M2 4.25A2.25 2.25 0 0 1 4.25 2h2.5A2.25 2.25 0 0 1 9 4.25v2.5A2.25 2.25 0 0 1 6.75 9h-2.5A2.25 2.25 0 0 1 2 6.75v-2.5ZM2 13.25A2.25 2.25 0 0 1 4.25 11h2.5A2.25 2.25 0 0 1 9 13.25v2.5A2.25 2.25 0 0 1 6.75 18h-2.5A2.25 2.25 0 0 1 2 15.75v-2.5ZM11 4.25A2.25 2.25 0 0 1 13.25 2h2.5A2.25 2.25 0 0 1 18 4.25v2.5A2.25 2.25 0 0 1 15.75 9h-2.5A2.25 2.25 0 0 1 11 6.75v-2.5ZM15.25 11.75a.75.75 0 0 0-1.5 0v2h-2a.75.75 0 0 0 0 1.5h2v2a.75.75 0 0 0 1.5 0v-2h2a.75.75 0 0 0 0-1.5h-2v-2Z" />
                            </svg>
                        </span>
                        <div class="fi-wg-panel-heading-text">
                            <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.available_widgets') }}</h3>
                            <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.available_widgets_help') }}</p>
                        </div>
                    </div>
                    <span class="fi-wg-accordion-chevron" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </button>

                <div class="fi-wg-accordion-body" x-show="isToolbarPanelOpen('catalog')" x-cloak>
                    <div class="fi-wg-search-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-search-icon" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                        <input
                            type="search"
                            x-model="catalogQuery"
                            placeholder="{{ __('filament-widget-grid::widget-grid.search_widgets') }}"
                            class="fi-input fi-wg-input fi-wg-search"
                            autocomplete="off"
                        />
                    </div>

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
                                <span class="fi-wg-catalog-label">{{ $widget['title'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            @if ($page->widgetGridPlugin()->hasTemplates())
                <div class="fi-wg-panels-row">
                    <section
                        class="fi-wg-panel fi-wg-panel--templates"
                        :class="{ 'fi-wg-panel--open': isToolbarPanelOpen('templates') }"
                    >
                        <button
                            type="button"
                            class="fi-wg-accordion-trigger"
                            x-on:click="toggleToolbarPanel('templates')"
                            :aria-expanded="isToolbarPanelOpen('templates').toString()"
                        >
                            <div class="fi-wg-panel-heading">
                                <span class="fi-wg-panel-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                                        <path d="M3.75 3A1.75 1.75 0 0 0 2 4.75v3.5C2 9.216 2.784 10 3.75 10h3.5A1.75 1.75 0 0 0 9 8.25v-3.5A1.75 1.75 0 0 0 7.25 3h-3.5ZM3.75 12A1.75 1.75 0 0 0 2 13.75v1.5c0 .966.784 1.75 1.75 1.75h1.5A1.75 1.75 0 0 0 7 15.25v-1.5A1.75 1.75 0 0 0 5.25 12h-1.5ZM12 3.75a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5h-3.5a.75.75 0 0 1-.75-.75ZM12 7.25a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5h-3.5a.75.75 0 0 1-.75-.75ZM12 10.75a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 0 1.5h-3.5a.75.75 0 0 1-.75-.75ZM12.75 14a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5h-3.5Z" />
                                    </svg>
                                </span>
                                <div class="fi-wg-panel-heading-text">
                                    <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.templates') }}</h3>
                                    <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.templates_help') }}</p>
                                </div>
                            </div>
                            <span class="fi-wg-accordion-chevron" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <div class="fi-wg-accordion-body" x-show="isToolbarPanelOpen('templates')" x-cloak>
                            <div class="fi-wg-template-form">
                                <input
                                    type="text"
                                    wire:model="widgetGridTemplateName"
                                    placeholder="{{ __('filament-widget-grid::widget-grid.template_name') }}"
                                    class="fi-input fi-wg-input"
                                />
                                <x-filament::button size="sm" color="primary" x-on:click.prevent="saveAsTemplate()">
                                    {{ __('filament-widget-grid::widget-grid.save_template') }}
                                </x-filament::button>
                            </div>

                            @php $myTemplates = $page->getMyWidgetGridTemplates(); @endphp
                            @if ($myTemplates->isNotEmpty())
                                <ul class="fi-wg-template-list">
                                    @foreach ($myTemplates as $template)
                                        <li class="fi-wg-template-row" wire:key="wg-my-template-{{ $template->id }}">
                                            <div class="fi-wg-template-meta">
                                                <span class="fi-wg-template-name">{{ $template->name }}</span>
                                                @if ($template->is_shared)
                                                    <span class="fi-wg-badge fi-wg-badge--shared">{{ __('filament-widget-grid::widget-grid.share') }}</span>
                                                @endif
                                            </div>
                                            <div class="fi-wg-template-actions">
                                                @if ($page->widgetGridPlugin()->userCanShareTemplates())
                                                    @if ($template->is_shared)
                                                        <x-filament::button size="xs" color="gray" outlined wire:click="unshareWidgetGridTemplate({{ $template->id }})" :disabled="$page->widgetGridLoadingTemplateId === $template->id">
                                                            {{ __('filament-widget-grid::widget-grid.unshare') }}
                                                        </x-filament::button>
                                                    @else
                                                        <x-filament::button size="xs" color="gray" outlined wire:click="shareWidgetGridTemplate({{ $template->id }})" :disabled="$page->widgetGridLoadingTemplateId === $template->id">
                                                            {{ __('filament-widget-grid::widget-grid.share') }}
                                                        </x-filament::button>
                                                    @endif
                                                @endif
                                                <x-filament::button size="xs" color="primary" wire:click="restoreWidgetGridTemplate({{ $template->id }})">
                                                    {{ __('filament-widget-grid::widget-grid.restore') }}
                                                </x-filament::button>
                                                <x-filament::button
                                                    size="xs"
                                                    color="danger"
                                                    outlined
                                                    wire:click="deleteWidgetGridTemplate({{ $template->id }})"
                                                    wire:confirm="{{ __('filament-widget-grid::widget-grid.delete_confirm') }}"
                                                    :disabled="$page->widgetGridLoadingTemplateId === $template->id"
                                                >
                                                    {{ __('filament-widget-grid::widget-grid.delete') }}
                                                </x-filament::button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="fi-wg-empty-hint">{{ __('filament-widget-grid::widget-grid.no_templates') }}</p>
                            @endif
                        </div>
                    </section>

                    <section
                        class="fi-wg-panel fi-wg-panel--shared"
                        :class="{ 'fi-wg-panel--open': isToolbarPanelOpen('shared') }"
                    >
                        <button
                            type="button"
                            class="fi-wg-accordion-trigger"
                            x-on:click="toggleToolbarPanel('shared')"
                            :aria-expanded="isToolbarPanelOpen('shared').toString()"
                        >
                            <div class="fi-wg-panel-heading">
                                <span class="fi-wg-panel-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                                        <path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.947 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 17a6.972 6.972 0 0 1-4.696-.81Z" />
                                    </svg>
                                </span>
                                <div class="fi-wg-panel-heading-text">
                                    <h3 class="fi-wg-panel-title">{{ __('filament-widget-grid::widget-grid.shared_templates') }}</h3>
                                    <p class="fi-wg-panel-help">{{ __('filament-widget-grid::widget-grid.shared_templates_help') }}</p>
                                </div>
                            </div>
                            <span class="fi-wg-accordion-chevron" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-wg-icon">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <div class="fi-wg-accordion-body" x-show="isToolbarPanelOpen('shared')" x-cloak>
                            @php $sharedTemplates = $page->getSharedWidgetGridTemplates(); @endphp
                            @if ($sharedTemplates->isNotEmpty())
                                <ul class="fi-wg-template-list">
                                    @foreach ($sharedTemplates as $template)
                                        <li class="fi-wg-template-row" wire:key="wg-shared-template-{{ $template->id }}">
                                            <div class="fi-wg-template-meta">
                                                <span class="fi-wg-template-name">{{ $template->name }}</span>
                                                <span class="fi-wg-template-by">{{ __('filament-widget-grid::widget-grid.by') }} {{ $template->user->name ?? '—' }}</span>
                                            </div>
                                            <x-filament::button size="xs" color="primary" wire:click="applySharedWidgetGridTemplate({{ $template->id }})">
                                                {{ __('filament-widget-grid::widget-grid.apply') }}
                                            </x-filament::button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="fi-wg-empty-hint">{{ __('filament-widget-grid::widget-grid.no_shared_templates') }}</p>
                            @endif
                        </div>
                    </section>
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
