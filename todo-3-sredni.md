# LiveTable – Etap 3: Priorytet Średni

> Priorytet: 🟡 Po zamknięciu etapów 1 i 2
> Podejście: TDD – najpierw test, potem implementacja

---

## 3.1 `FilterType` enum zamiast magicznego stringa

**Plik:** `src/Filter.php:11`

`type = 'text' | 'select' | 'date'` to plain string. Brak type safety, brak
autocompletion w IDE, dodanie nowego typu wymaga modyfikacji widoku i
dokumentacji (naruszenie OCP).

**Test (najpierw):**
```php
// tests/Unit/FilterTest.php – dodać:
it('Filter::text creates FilterType::TEXT', function () {
    $f = Filter::text('search', 'Szukaj');
    expect($f->type)->toBe(FilterType::TEXT);
});

it('Filter::select creates FilterType::SELECT', function () {
    $f = Filter::select('status', 'Status', ['active' => 'Aktywny']);
    expect($f->type)->toBe(FilterType::SELECT);
});

it('Filter::date creates FilterType::DATE', function () {
    $f = Filter::date('created_at', 'Data');
    expect($f->type)->toBe(FilterType::DATE);
});
```

**Rozwiązanie:**
```php
// src/Enums/FilterType.php
enum FilterType: string
{
    case TEXT   = 'text';
    case SELECT = 'select';
    case DATE   = 'date';
}

// src/Filter.php – zmienić typ
public function __construct(
    public readonly string     $key,
    public readonly string     $label,
    public readonly FilterType $type    = FilterType::TEXT,
    public readonly array      $options = [],
) {}
```

Zaktualizować widok Blade – `$filter->type->value` zamiast `$filter->type`.

---

## 3.2 `?string $model = null` zamiast `$model = ''`

**Plik:** `src/BaseTable.php:91`

Pusty string jako sentinel wartość to anty-wzorzec. Każde sprawdzenie to
`$this->model === ''` zamiast czytelniejszego `$this->model === null`.

**Test (najpierw):**
```php
it('hasModel returns false when model is null', function () {
    $table = stubTable();
    // model nie ustawiony
    expect($table->hasModel())->toBeFalse();
});

it('hasModel returns true when valid model class set', function () {
    $table = stubTable();
    // model ustawiony na istniejącą klasę
    expect($table->hasModel())->toBeTrue();
});
```

**Rozwiązanie:**
```php
protected ?string $model = null;
```

Wszystkie sprawdzenia zamienić na `$this->model !== null && class_exists($this->model)`.

---

## 3.3 Ekstrakcja `hasModel()` – eliminacja duplikacji

**Plik:** `src/BaseTable.php` – wzorzec `$this->model === '' || ! class_exists($this->model)` pojawia się 6+ razy

**Rozwiązanie:**
```php
private function hasModel(): bool
{
    return $this->model !== null
        && $this->model !== ''
        && class_exists($this->model);
}
```

Zastąpić wszystkie wystąpienia wywołaniem `$this->hasModel()`.
Upewnić się że testy z etapu 3.2 obejmują ten przypadek.

---

## 3.4 `FieldDefinition` value object zamiast tablicy asocjacyjnej

**Plik:** `src/BaseTable.php:625`

```php
// Teraz: array{key: string, label: string, type: string}
// Brak type safety, IDE nie podpowiada kluczy
return [
    'key'   => $key,
    'label' => Str::headline($key),
    'type'  => $this->resolveFieldType($key, $casts, $table),
];
```

**Test (najpierw):**
```php
it('creatingFields returns array of FieldDefinition objects', function () {
    $fields = $table->creatingFields();
    expect($fields[0])->toBeInstanceOf(FieldDefinition::class);
    expect($fields[0]->key)->toBe('name');
    expect($fields[0]->label)->toBe('Name');
    expect($fields[0]->type)->toBe('text');
});
```

**Rozwiązanie:**
```php
// src/ValueObjects/FieldDefinition.php
readonly class FieldDefinition
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
    ) {}
}
```

Zaktualizować `creatingFields()`, widok Blade (`$field->key` zamiast `$field['key']`),
testy unit korzystające z `creatingFields()`.

---

## 3.5 Wyodrębnić ikony SVG `defaultActions` poza `render()`

**Plik:** `src/BaseTable.php:1329-1338`

Hardkodowane SVG w metodzie `render()` uniemożliwiają zmianę ikon
bez modyfikacji klasy bazowej (naruszenie OCP).

**Test (najpierw):**
```php
it('default edit row action uses $defaultEditIcon property', function () {
    $table = stubTableWithDefaultActions();
    $table->defaultEditIcon = '<svg id="custom-edit"></svg>';
    // render() powinien użyć customowej ikony
});
```

**Rozwiązanie:**
```php
protected string $defaultEditIcon   = '<svg ...>...</svg>';  // obecna ikona jako default
protected string $defaultDeleteIcon = '<svg ...>...</svg>';  // obecna ikona jako default
```

W `render()` używać `$this->defaultEditIcon` zamiast wbudowanego SVG stringa.

---

## 3.6 Rozbić `render()` na pomocnicze metody prywatne

**Plik:** `src/BaseTable.php:1259-1418` (~160 linii, 30+ zmiennych do widoku)

Metoda `render()` powinna być jak najcieńsza. Całą logikę przygotowania
zmiennych warto wyciągnąć do prywatnych metod grupujących powiązane dane.

**Rozwiązanie (bez testów – metody prywatne, testowane pośrednio):**
```php
public function render(): mixed
{
    return view('live-table::base-table', array_merge(
        $this->buildPaginationData(),
        $this->buildColumnData(),
        $this->buildSelectionData(),
        $this->buildActionsData(),
        $this->buildModalData(),
        $this->buildExportData(),
    ));
}

private function buildPaginationData(): array
{
    $query = $this->buildQuery();
    $total = $query->count();
    // ... paginacja, infiniteMode, from, to, pages
    return compact('items', 'total', 'lastPage', 'from', 'to', 'pages', 'infiniteMode', 'allLoaded');
}

private function buildColumnData(): array { /* visibleColumns, allColumns, sumData, countData */ }
private function buildSelectionData(): array { /* selectable, hasCheckboxCol, currentPageIds, allPageSelected */ }
private function buildActionsData(): array { /* rowActionsMap, hasRowActions, bulkActionDefs, headerActionDefs */ }
private function buildModalData(): array { /* creatingFields, editingFields, massEditFields, modals */ }
private function buildExportData(): array { /* exportCsv, exportPdf, massDeleteEnabled, massEditEnabled */ }
```

---

## 3.7 N+1 w sub-rows – dodać `preloadSubRows()` hook

**Plik:** `src/BaseTable.php:1310-1315`

```php
foreach ($items as $item) {
    $sub = $this->subRows($item);  // może wykonać N zapytań DB
}
```

Istniejące ostrzeżenie w docbloku jest niewystarczające – brak mechanizmu
batch-loadingu bez konieczności modyfikacji `baseQuery()`.

**Test (najpierw):**
```php
it('preloadSubRows is called before iterating subRows', function () {
    $called = false;
    $table  = new class extends BaseTableStub {
        protected function preloadSubRows(Collection $items): void
        {
            // oznacz że wywołano
        }
    };
    // render() powinien wywołać preloadSubRows($items) przed pętlą
});
```

**Rozwiązanie:**
```php
/**
 * Called once before the per-row subRows() loop with all current page items.
 * Override to eager-load relations in batch (prevents N+1 queries).
 *
 * Example:
 *   $items->load('projects.tasks');
 */
protected function preloadSubRows(Collection $items): void {}
```

W `render()` przed pętlą:
```php
if ($this->expandable) {
    $this->preloadSubRows($items);
    foreach ($items as $item) { ... }
}
```

---

## 3.8 Opcjonalne: `staticRowActions()` dla akcji niezależnych od wiersza

**Plik:** `src/BaseTable.php:1322-1354`

Gdy `rowActions()` wykonuje logikę per-wiersz (np. sprawdza uprawnienia),
jest wywoływany N razy na stronę. Dla akcji identycznych dla każdego wiersza
jest to zbędna praca.

**Rozwiązanie:**
```php
/**
 * Actions that are the same for every row (computed once, not per-row).
 * Use this when actions don't depend on row data.
 * Use rowActions(mixed $row) when actions depend on row content/permissions.
 *
 * @return RowAction[]
 */
protected function staticRowActions(): array
{
    return [];
}
```

W `render()`:
```php
$staticActions = $this->staticRowActions();

foreach ($items as $item) {
    $actions = array_merge($staticActions, $this->rowActions($item));
    // ...
}
```

---

## 3.9 Limit `$selected` w UI

**Plik:** `resources/views/bootstrap/base-table.blade.php`

Gdy użytkownik zaznaczy tysiące wierszy, cała tablica `$selected` jest
serializowana do Livewire snapshot przy każdym żądaniu → spowolnienie.

**Rozwiązanie (po implementacji 1.6):**

Dodać widoczny licznik i ostrzeżenie gdy `count($selected) > 500`:
```blade
@if(count($this->selected) > 500)
    <div class="alert alert-warning py-1 small">
        Zaznaczono {{ count($this->selected) }} rekordów – może to spowolnić działanie.
    </div>
@endif
```