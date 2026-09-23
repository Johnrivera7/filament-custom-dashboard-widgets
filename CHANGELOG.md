# Changelog

All notable changes to `filament-widget-grid` will be documented in this file.

## Unreleased

- Fix GridStack collapsing a 24-column collage to 12 (`columnOpts.columnMax` now matches configured columns).
- Ship `.gs-24` width/left CSS (GridStack only includes 1 and 12 out of the box); without it, 24-col cells render at 0 width.
- Stats overview cells pick 1–4 columns from **width only** (no longer forced to portrait when the cell is tall-but-wide), so cards fill the widget instead of leaving a dead right half.
- Default stats overview width is half the collage (not full-width) so charts can sit side-by-side.

## 1.0.2 - 2026-08-24

- Fix `filament-hidden` by applying the class directly on `img` tags.

## 1.0.1 - 2026-08-22

- Rebrand display name to **Customizable Dashboard Widgets** (Filament directory) to distinguish from the official Custom Dashboards plugin.

## 1.0.0 - 2026-08-20

- Initial release for Filament v5: permission-aware widget catalog, GridStack drag-and-drop, resize handles, per-user layouts, default layouts, lock, and shareable templates.
- Collage grid: 24 columns, 45px rows, resize from all edges, leftover gaps, and chart reflow when a cell is resized.
- Widgets float by default so they can sit in any leftover cell instead of packing into a rigid masonry.
- Default cell sizes follow the widget type (stats ~half width, charts ~half width and 450px tall). Tiny leftover 4×4 auto-layouts are inflated on load. Chart legends and labels reflow to the cell width.
- Reset to the panel default, catalog search, snap guides while editing, Escape to cancel, single-column phones, content-sized stats/tables, and comfortable/compact density.
