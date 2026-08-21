# Changelog

All notable changes to `filament-widget-grid` will be documented in this file.

## 1.0.0 - 2026-08-20

- Initial release for Filament v5: permission-aware widget catalog, GridStack drag-and-drop, resize handles, per-user layouts, default layouts, lock, and shareable templates.
- Collage grid: 24 columns, 45px rows, resize from all edges, leftover gaps, and chart reflow when a cell is resized.
- Widgets float by default so they can sit in any leftover cell instead of packing into a rigid masonry.
- Default cell sizes follow the widget type (stats full-width, charts ~half width and 450px tall). Tiny leftover 4×4 auto-layouts are inflated on load. Chart legends and labels reflow to the cell width.
- Reset to the panel default, catalog search, snap guides while editing, Escape to cancel, single-column phones, content-sized stats/tables, and comfortable/compact density.
