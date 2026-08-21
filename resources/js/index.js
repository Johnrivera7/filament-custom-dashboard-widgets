import { GridStack } from 'gridstack'

export default function filamentWidgetGrid({
    editable = false,
    columns = 24,
    cellHeight = 45,
    float = true,
    margin = 8,
}) {
    return {
        editable,
        catalogQuery: '',
        grid: null,
        reflowTimer: null,
        resizeObserver: null,
        onWindowResize: null,

        init() {
            this.bootGrid()

            this.$watch('editable', () => {
                this.bootGrid()
            })
        },

        isMobile() {
            return window.matchMedia('(max-width: 767px)').matches
        },

        canEdit() {
            return this.editable && ! this.isMobile()
        },

        matchesCatalog(title) {
            const query = this.catalogQuery.trim().toLowerCase()

            return query === '' || String(title).toLowerCase().includes(query)
        },

        onEscape(event) {
            if (! this.editable) {
                return
            }

            const tag = event.target?.tagName

            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return
            }

            this.$wire.cancelWidgetGridEditing()
        },

        bootGrid() {
            if (this.grid) {
                this.grid.destroy(false)
                this.grid = null
            }

            const root = this.$refs.grid

            if (!root) {
                return
            }

            const canEdit = this.canEdit()

            root.style.setProperty('--fi-wg-columns', String(columns))
            root.style.setProperty('--fi-wg-cell-height', `${cellHeight}px`)

            this.grid = GridStack.init(
                {
                    column: columns,
                    cellHeight,
                    float,
                    margin,
                    animate: true,
                    disableDrag: ! canEdit,
                    disableResize: ! canEdit,
                    alwaysShowResizeHandle: canEdit,
                    handle: '.fi-wg-item-content',
                    resizable: { handles: 'n, e, s, w, ne, se, sw, nw' },
                    minRow: 1,
                    sizeToContent: false,
                    columnOpts: {
                        breakpointForWindow: true,
                        layout: 'moveScale',
                        breakpoints: [{ w: 768, c: 1, layout: 'list' }],
                    },
                },
                root,
            )

            this.grid.float(float)
            this.fitContentWidgets()

            this.grid.on('change', () => this.sync())
            this.grid.on('resizestop', (_event, el) => this.scheduleReflow(el))
            this.observeChartCells(root)
            setTimeout(() => this.scheduleReflow(), 200)
            setTimeout(() => this.scheduleReflow(), 700)
            setTimeout(() => this.scheduleReflow(), 1400)
            setTimeout(() => this.fitContentWidgets(), 400)
        },

        fitContentWidgets() {
            if (! this.grid || typeof this.grid.resizeToContent !== 'function') {
                return
            }

            this.grid.getGridItems().forEach((el) => {
                if (el.getAttribute('gs-size-to-content')) {
                    try {
                        this.grid.resizeToContent(el)
                    } catch {
                        // GridStack ignores nodes that cannot shrink to content.
                    }
                }
            })
        },

        observeChartCells(root) {
            this.resizeObserver?.disconnect()
            this.onWindowResize && window.removeEventListener('resize', this.onWindowResize)

            this.resizeObserver = new ResizeObserver((entries) => {
                entries.forEach((entry) => {
                    const item = entry.target.closest('.grid-stack-item')

                    if (
                        item?.classList.contains('ui-resizable-resizing') ||
                        item?.classList.contains('ui-draggable-dragging')
                    ) {
                        return
                    }

                    this.scheduleReflow(item)
                })
            })

            root.querySelectorAll('.grid-stack-item-content').forEach((cell) => {
                this.resizeObserver.observe(cell)
            })

            this.onWindowResize = () => {
                if (this.grid && this.editable) {
                    const canEdit = this.canEdit()
                    this.grid.enableMove(canEdit)
                    this.grid.enableResize(canEdit)
                }

                this.scheduleReflow()
            }
            window.addEventListener('resize', this.onWindowResize)
        },

        scheduleReflow(el) {
            clearTimeout(this.reflowTimer)
            this.reflowTimer = setTimeout(() => this.reflowCharts(el), 80)
        },

        reflowCharts(scope) {
            const root = this.$refs.grid

            if (!root) {
                return
            }

            const items = scope
                ? [scope.el ?? scope]
                : Array.from(root.querySelectorAll('.grid-stack-item'))

            items.forEach((item) => {
                if (!(item instanceof HTMLElement)) {
                    return
                }

                const content = item.querySelector('.grid-stack-item-content')

                if (!content) {
                    return
                }

                const header = content.querySelector('.fi-section-header')
                const chartWrap =
                    content.querySelector('.fi-section-content') ??
                    content.querySelector('.filament-apex-charts-chart')
                const available = chartWrap
                    ? this.innerBoxHeight(chartWrap)
                    : content.clientHeight - (header?.offsetHeight ?? 0)
                const height = Math.max(120, available - 4)
                const width = Math.max(
                    1,
                    (chartWrap instanceof HTMLElement ? chartWrap.clientWidth : content.clientWidth) -
                        this.horizontalPadding(chartWrap ?? content),
                )
                const portrait = height > width * 0.92

                this.apexChartsIn(content).forEach((chart) => {
                    this.adaptApexChart(chart, { height, width, portrait })
                })

                content.querySelectorAll('canvas').forEach((canvas) => {
                    window.Chart?.getChart?.(canvas)?.resize?.()
                })
            })
        },

        innerBoxHeight(el) {
            const style = window.getComputedStyle(el)

            return Math.max(
                0,
                el.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom),
            )
        },

        horizontalPadding(el) {
            if (!(el instanceof HTMLElement)) {
                return 0
            }

            const style = window.getComputedStyle(el)

            return parseFloat(style.paddingLeft) + parseFloat(style.paddingRight)
        },

        apexChartsIn(content) {
            const charts = []
            const seen = new Set()

            content.querySelectorAll('[x-data]').forEach((el) => {
                try {
                    const chart = window.Alpine?.$data?.(el)?.chart

                    if (chart && typeof chart.updateOptions === 'function' && !seen.has(chart)) {
                        seen.add(chart)
                        charts.push(chart)
                    }
                } catch {
                    // The node may not be an Alpine component yet.
                }
            })

            content.querySelectorAll('.filament-apex-charts-chart-object[id]').forEach((node) => {
                try {
                    const chart = window.ApexCharts?.getChartByID?.(node.id)

                    if (chart && typeof chart.updateOptions === 'function' && !seen.has(chart)) {
                        seen.add(chart)
                        charts.push(chart)
                    }
                } catch {
                    // Chart id may not be registered on window.ApexCharts.
                }
            })

            return charts
        },

        adaptApexChart(chart, { height, width, portrait }) {
            const type = chart.w?.config?.chart?.type
            const cramped = width < 380
            const veryCramped = width < 280
            const fontSize = veryCramped ? '10px' : cramped ? '11px' : '12px'
            const legendHidden = chart.w?.config?.legend?.show === false
            const yaxis = chart.w?.config?.yaxis
            const yTitle = Array.isArray(yaxis) ? yaxis[0]?.title?.text : yaxis?.title?.text
            const options = {
                chart: {
                    height,
                    width: '100%',
                    parentHeightOffset: 0,
                    offsetY: 0,
                },
                grid: {
                    padding: {
                        top: 8,
                        right: cramped ? 4 : 8,
                        bottom: cramped ? 4 : 8,
                        left: cramped ? 4 : 8,
                    },
                },
                xaxis: {
                    labels: {
                        show: height > 140,
                        rotate: width < 520 ? -45 : 0,
                        hideOverlappingLabels: true,
                        trim: true,
                        style: { fontSize },
                    },
                },
                yaxis: {
                    labels: {
                        show: width > 200,
                        style: { fontSize },
                        maxWidth: cramped ? 32 : 48,
                    },
                    title: {
                        text: width < 300 ? '' : (yTitle ?? ''),
                    },
                },
            }

            if (legendHidden) {
                options.legend = { show: false }
            } else {
                options.legend = {
                    show: true,
                    position: type === 'donut' || type === 'pie'
                        ? (cramped || portrait ? 'bottom' : 'right')
                        : 'bottom',
                    fontSize,
                    offsetY: 0,
                    height: cramped ? 52 : 40,
                    itemMargin: {
                        horizontal: cramped ? 6 : 12,
                        vertical: cramped ? 2 : 6,
                    },
                    formatter: (name) => (
                        cramped
                            ? String(name ?? '').replace(/\s*\([^)]*\)\s*/g, '').trim()
                            : String(name ?? '')
                    ),
                }
            }

            if (type === 'bar') {
                options.plotOptions = {
                    bar: {
                        horizontal: portrait,
                    },
                }
                options.dataLabels = {
                    enabled: !cramped && chart.w?.config?.dataLabels?.enabled !== false,
                }
                options.xaxis = {
                    labels: {
                        rotate: portrait ? 0 : (width < 520 ? -45 : 0),
                        hideOverlappingLabels: true,
                        trim: true,
                        style: { fontSize },
                    },
                }
            }

            if (type === 'donut' || type === 'pie') {
                options.dataLabels = {
                    enabled: !veryCramped,
                    style: { fontSize },
                }
                options.plotOptions = {
                    pie: {
                        donut: {
                            size: cramped ? '58%' : '62%',
                        },
                    },
                }
            }

            try {
                chart.updateOptions(options, true, false)
            } catch {
                // Ignore chart libraries that reject a partial options patch.
            }
        },

        sync() {
            if (!this.grid || !this.editable) {
                return
            }

            const items = this.grid.save(false).map((node) => ({
                widget: String(node.id),
                x: Number(node.x ?? 0),
                y: Number(node.y ?? 0),
                w: Number(node.w ?? 1),
                h: Number(node.h ?? 1),
                visible: true,
            }))

            this.$wire.set('widgetGridLayout', items, false)
        },

        toggleWidget(widgetClass, visible) {
            this.sync()
            this.$wire.toggleWidgetOnGrid(widgetClass, visible)
        },

        destroy() {
            clearTimeout(this.reflowTimer)
            this.resizeObserver?.disconnect()
            this.resizeObserver = null

            if (this.onWindowResize) {
                window.removeEventListener('resize', this.onWindowResize)
                this.onWindowResize = null
            }

            if (this.grid) {
                this.grid.destroy(false)
                this.grid = null
            }
        },
    }
}
