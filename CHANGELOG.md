# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added
- Default actions (edit/delete modals via `$defaultActions = true`)
- Default creating modal (`$defaultCreating = true`) with auto-detected field types
- Mass edit modal (`$massEdit = true`)
- Mass delete with confirmation modal (`$massDelete = true`)
- Summary/count aggregate columns (`$sumColumns`, `$countColumns`, `AggregateScope`)
- `authorizeAction()` hook for authorization in destructive operations
- `$creatableFields` / `$editableFields` whitelists for form field exposure control
- Automatic password hashing for `password` / `*_password` fields (`$autoHashPasswords`)
- `$maxSelected` limit to prevent oversized Livewire payloads
- `SubRows` lazy loading – `fromQuery()` defers DB query until `getItems()` is called
- `BulkAction::tooltip()` fluent method for explicit tooltip text
- `TableStateRepositoryInterface` + `EloquentTableStateRepository` (DIP for state persistence)
- `CellInterface` and `EditableCellInterface` contracts in `src/Contracts/`
- Feature tests: render, sorting, search, selection (`tests/Feature/`)
- Unit tests for all Cell types (`tests/Unit/Cells/`)
- Security tests (`tests/Unit/SecurityTest.php`)
- Blade view split into `@include` partials (`resources/views/bootstrap/partials/`)
- Single `selectRaw()` query for aggregates (replaces N separate queries)
- Memory-efficient CSV export via `cursor()` streaming
- Traits refactor: `BaseTable` split into 11 `src/Concerns/` traits (SRP)
- Clone pattern for `Action`, `BulkAction`, `RowAction` fluent builders
- `InstallCommand` skips migration publishing when migration already exists

### Changed
- `$persistState` changed from `public` to `protected` (prevents client-side override)

---

## [0.1.0] – Initial release

### Added
- `BaseTable` abstract Livewire component with data pipeline
- `Column` fluent API with cell types: text, number, date, dateTime, time, money, link, badge, checkbox, select, custom
- `Filter` (text / select / date) displayed in modal
- `Action` header actions (link or Livewire method)
- `BulkAction` for selected rows
- `RowAction` per-row action buttons
- Pagination (10 / 25 / 50 / 100 / 200 rows per page)
- Debounced search (400 ms)
- Column sorting (click header)
- Column visibility toggle (dropdown)
- Drag & drop column reordering (Alpine.js + SortableJS)
- Row selection with bulk actions
- Inline editable cells: `CheckboxCell`, `SelectCell`
- State persistence to DB (`TableState` model + migration)
- Infinite scroll mode (`$perPage = 0`)
- Sub-rows / expandable rows
- Bootstrap 5 and Tailwind CSS views
- `live-table:install` Artisan command
- Pest PHP test suite (unit + feature)