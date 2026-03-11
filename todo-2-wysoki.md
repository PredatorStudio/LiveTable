# LiveTable – Etap 2: Priorytet Wysoki

> Priorytet: 🟠 Po zamknięciu etapu 1
> Podejście: TDD – najpierw test, potem implementacja

---

## 2.1 [SRP] Podział `BaseTable` na traity

**Plik:** `src/BaseTable.php` (1 420 linii, ~10 odpowiedzialności)

Klasa narusza Single Responsibility Principle obsługując jednocześnie:
paginację, sortowanie, filtrowanie, selekcję, bulk actions, eksport,
persystencję stanu, CRUD, mass edit/delete, infinite scroll, agregaty, sub-rows.

**Plan podziału – katalog `src/Concerns/`:**

| Trait | Metody |
|---|---|
| `ManagesSorting` | `sort()`, walidacja `$sortDir` |
| `ManagesFilters` | `applyActiveFilters()`, `clearFilters()`, `removeFilter()` |
| `ManagesSelection` | `toggleSelectRow()`, `selectRows()`, `deselectRows()` |
| `ManagesBulkActions` | `selectAllFromQuery()`, `clearSelectAllQuery()` |
| `ManagesDefaultCrud` | `openEditingModal()`, `updateRecord()`, `deleteRow()`, `openCreatingModal()`, `createRecord()`, `creatingFields()`, `resolveFieldType()`, `typeFromSchema()` |
| `ManagesMassActions` | `openMassEditModal()`, `massEditUpdate()`, `massDelete()` |
| `ManagesExport` | `exportCsv()`, `exportPdf()`, `getExportRows()`, `rowToCsvArray()` |
| `ManagesStatePersistence` | `saveState()`, `loadState()`, `resolveClientIdentifier()`, `getTableIdentifier()` |
| `ManagesInfiniteScroll` | `loadMore()`, `resetInfiniteScroll()` |
| `ManagesAggregates` | `computeAggregates()` |
| `ManagesColumns` | `resolvedColumns()`, `visibleColumns()`, `cachedColumns()`, `toggleColumn()`, `reorderColumns()` |

`BaseTable` po refaktorze:
```php
abstract class BaseTable extends Component
{
    use ManagesSorting,
        ManagesFilters,
        ManagesSelection,
        ManagesBulkActions,
        ManagesDefaultCrud,
        ManagesMassActions,
        ManagesExport,
        ManagesStatePersistence,
        ManagesInfiniteScroll,
        ManagesAggregates,
        ManagesColumns;

    // tylko properties i metody abstrakcyjne
}
```

**Kolejność wykonania (TDD):**
1. Jeden trait na raz – przenieść metody
2. Uruchomić istniejące testy po każdym przeniesieniu (`make test`)
3. Nowe testy pisać do traitów w izolacji

---

## 2.2 N+1 zapytań w `computeAggregates()` z scope ALL

**Plik:** `src/BaseTable.php:1245-1253`

```php
// PROBLEM: każda kolumna = osobne zapytanie SQL
foreach ($this->sumColumns as $col) {
    $sumData[$col] = $aggQuery->sum($col);        // SELECT SUM(col) per kolumna
}
foreach ($this->countColumns as $col) {
    $countData[$col] = $this->buildQuery()->whereNotNull($col)->count(); // jedno zapytanie!
}
```

Dla 5 sum + 3 count = 9 zapytań zamiast 1.

**Test (najpierw):**
```php
it('computeAggregates executes single query for all columns', function () {
    // mock Builder z assertem że selectRaw jest wywołany raz
    // a nie sum() i count() wielokrotnie
});
```

**Rozwiązanie – jedno zapytanie `selectRaw()`:**
```php
private function computeAggregates(Collection $pageItems): array
{
    if (empty($this->sumColumns) && empty($this->countColumns)) {
        return [[], []];
    }

    if ($this->aggregateScope === AggregateScope::PAGE) {
        $sumData   = array_combine(
            $this->sumColumns,
            array_map(fn($col) => $pageItems->sum($col), $this->sumColumns),
        );
        $countData = array_combine(
            $this->countColumns,
            array_map(fn($col) => $pageItems->whereNotNull($col)->count(), $this->countColumns),
        );
        return [$sumData, $countData];
    }

    // ALL scope – jedno zapytanie
    $selects = [];
    foreach ($this->sumColumns as $col) {
        $selects[] = 'SUM(' . $this->quoteColumn($col) . ') as __sum_' . $col;
    }
    foreach ($this->countColumns as $col) {
        $selects[] = 'COUNT(' . $this->quoteColumn($col) . ') as __count_' . $col;
    }

    $row = $this->buildQuery()->selectRaw(implode(', ', $selects))->first();

    $sumData   = [];
    $countData = [];

    foreach ($this->sumColumns as $col) {
        $sumData[$col] = $row ? ($row->{'__sum_' . $col} ?? 0) : 0;
    }
    foreach ($this->countColumns as $col) {
        $countData[$col] = $row ? ($row->{'__count_' . $col} ?? 0) : 0;
    }

    return [$sumData, $countData];
}

private function quoteColumn(string $col): string
{
    // Tylko alfanumeryczne i _ – zapobiega injection przez $sumColumns
    if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $col)) {
        throw new \InvalidArgumentException("Nieprawidłowa nazwa kolumny: {$col}");
    }
    return '`' . str_replace('`', '', $col) . '`';
}
```

> **Uwaga bezpieczeństwa:** `$sumColumns` i `$countColumns` to protected properties
> ustawiane przez dewelopera (nie przez użytkownika), ale walidacja `quoteColumn()`
> jest warstwą defensywną.

---

## 2.3 Eksport CSV ładuje wszystkie wiersze do pamięci

**Plik:** `src/BaseTable.php:835-843`

```php
return $query->get(); // może załadować 100 000+ wierszy do pamięci
```

**Test (najpierw):**
```php
it('exportCsv uses cursor instead of get for large datasets', function () {
    // mock Builder – assert że cursor() jest wywołany, nie get()
});
```

**Rozwiązanie – `cursor()` + lazy iteration:**

Zmienić `getExportRows()` na metodę zwracającą `Builder` (nie Collection),
a w `exportCsv()` iterować przez `cursor()`:

```php
public function exportCsv(): StreamedResponse
{
    $columns  = $this->visibleColumns();
    $query    = $this->buildExportQuery();
    $filename = class_basename(static::class) . '_' . now()->format('Y-m-d_His') . '.csv';

    return response()->streamDownload(function () use ($query, $columns) {
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, array_map(fn(Column $col) => $col->label, $columns));

        foreach ($query->cursor() as $row) {
            fputcsv($output, $this->rowToCsvArray($row, $columns));

            if ($this->expandable) {
                foreach ($this->subRows($row)?->getItems() ?? [] as $subRow) {
                    fputcsv($output, $this->rowToCsvArray($subRow, $columns));
                }
            }
        }

        fclose($output);
    }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
}

private function buildExportQuery(): Builder
{
    $query = $this->buildQuery();

    if (! $this->selectAllQuery && ! empty($this->selected)) {
        $query->whereIn($this->primaryKey, $this->selected);
    }

    return $query;
}
```

---

## 2.4 Testy Feature – integracyjne Livewire

**Katalog:** `tests/Feature/` – nie istnieje!

Brak testów sprawdzających pełny cykl Livewire z wyrenderowanym komponentem.

**Do stworzenia (TDD – pisać przed implementacją feature'ów):**

```
tests/Feature/
  BaseTableRenderTest.php     – komponent się renderuje, widać kolumny
  BaseTableSortingTest.php    – klik w nagłówek → zmiana sortowania
  BaseTableSearchTest.php     – wpisanie frazy → przefiltrowane wyniki
  BaseTableFiltersTest.php    – otwarcie modalu, apply, clear, tag
  BaseTableCrudTest.php       – create/edit/delete przez modal (defaultActions)
  BaseTableExportTest.php     – response CSV ma poprawne nagłówki HTTP i dane
  BaseTableSelectionTest.php  – selectAll, deselectAll, selectAllFromQuery
```

**Przykładowy test Feature:**
```php
uses(\Livewire\LivewireServiceProvider::class);

it('sorts by column on header click', function () {
    Livewire::test(UsersTable::class)
        ->assertSee('Jan')
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDir', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDir', 'desc');
});
```

**Wymagania:**
- Dołączyć `livewire/livewire` do dev dependencies jeśli jeszcze nie ma
- Skonfigurować in-memory SQLite w `phpunit.xml` / `testbench.yaml`

---

## 2.5 Testy bezpieczeństwa

Żaden istniejący test nie sprawdza ścieżek bezpieczeństwa.

**Do stworzenia w `tests/Unit/SecurityTest.php`:**

```php
it('sort() rejects column not in sortable list', function () {
    $table = stubTable(); // kolumna 'email' nie jest sortable
    $table->sort('email');
    expect($table->sortBy)->toBe('');
});

it('render() ignores $sortBy set outside sort() method', function () {
    // Symulacja ataku: bezpośrednie ustawienie $sortBy
    $table = stubTable();
    $table->sortBy = 'users; DROP TABLE users--';
    // buildQuery() nie powinien zawierać tej wartości w ORDER BY
});

it('sortDir is clamped to asc when invalid value provided', function () {
    $table = stubTable();
    $table->sortDir = 'INVALID VALUE';
    // po naprawie z etapu 1 – oczekujemy 'asc'
});

it('deleteRow does nothing when $defaultActions is false', function () {
    $table = stubTable(); // defaultActions = false (domyślnie)
    $table->deleteRow('1');
    // brak wyjątku, brak DB query
});

it('massEditUpdate only updates allowed fields from $fillable', function () {
    // massEditData zawiera pole 'is_admin' spoza $creatableFields
    // oczekujemy że pole jest odfiltrowane
});

it('createRecord filters out non-fillable keys from $creatingData', function () {
    // creatingData zawiera 'hacked_field'
    // oczekujemy że Model::create() nie dostaje tego pola
});
```

---

## 2.6 Testy dla Cell rendering

Brak testów dla klas w `src/Cells/`.

**Do stworzenia w `tests/Unit/Cells/`:**

```php
// BadgeCellTest.php
it('renders badge with correct color from map', fn() =>
    expect((new BadgeCell(['active' => 'success']))->render(new stdClass, 'active'))
        ->toContain('bg-success')
        ->toContain('active')
);

it('renders empty badge for unmapped value', fn() =>
    expect((new BadgeCell([]))->render(new stdClass, null))
        ->toContain('text-muted')
);

// MoneyCellTest.php
it('formats money with space as thousand separator and comma as decimal', fn() =>
    expect((new MoneyCell(MoneyFormat::SPACE_COMMA))->render(new stdClass, 1234567.89))
        ->toContain('1 234 567,89')
);

// DateCellTest.php
it('formats date as d.m.Y by default', fn() =>
    expect((new DateCell())->render(new stdClass, '2024-06-15'))
        ->toBe('15.06.2024')
);

it('renders empty for null date', fn() =>
    expect((new DateCell())->render(new stdClass, null))
        ->toContain('—')
);

// LinkCellTest.php
it('resolves URL from closure', fn() =>
    expect((new LinkCell(fn($row) => '/users/' . $row->id))->render(
        (object) ['id' => 42], 'Jan'
    ))->toContain('href="/users/42"')
);
```

---

## 2.7 Testy dla `resolveFieldType()` i `typeFromSchema()`

**Plik:** `src/BaseTable.php:699-767`

Logika rozpoznawania typów pól nie jest pokryta testami.

**Do stworzenia** z in-memory SQLite lub mockiem `Schema`:

```php
it('resolveFieldType returns email type for email field name', function () {
    // stub z $fillable = ['email'] i Schema mockującym 'string'
    // oczekujemy type = 'email'
});

it('resolveFieldType returns password type for password field', function () {
    // ...
});

it('resolveFieldType respects config creating_field_types override', function () {
    config(['live-table.creating_field_types' => ['phone' => 'tel']]);
    // pole 'phone' → typ 'tel'
});
```