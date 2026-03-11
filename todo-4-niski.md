# LiveTable – Etap 4: Priorytet Niski

> Priorytet: 🟢 Refaktoring architektoniczny i porządki
> Podejście: TDD tam gdzie ma sens; niektóre zadania to czysta reorganizacja

---

## 4.1 Interfejsy PHP – lepsza testowalność i wymienność

**Brak `src/Contracts/`**

Projekt nie definiuje żadnych PHP interfaces. Utrudnia:
- mockowanie w testach
- tworzenie alternatywnych implementacji (np. nieEloquentowy data source)
- DIP (Dependency Inversion)

**Rozwiązanie – dodać interfejsy:**

```
src/Contracts/
  CellInterface.php
    + render(mixed $row, mixed $value): string

  EditableCellInterface.php extends CellInterface
    + renderEditable(mixed $row, mixed $value, string $rowId): string
    + update(mixed $row, mixed $value): void
    + validate(mixed $value): void

  TableStateRepositoryInterface.php
    + save(string $tableId, array $identifier, array $state): void
    + load(string $tableId, array $identifier): ?array
    + delete(string $tableId, array $identifier): void
```

`Cell` i `EditableCell` implementują odpowiednie interfejsy.
`EloquentTableStateRepository` implementuje `TableStateRepositoryInterface`.

---

## 4.2 [DIP] `TableStateRepositoryInterface` + `EloquentTableStateRepository`

**Plik:** `src/BaseTable.php:895-935`

`BaseTable` bezpośrednio wywołuje `TableState::updateOrCreate(...)`.
Naruszenie Dependency Inversion – komponent orkiestrujący zna konkretny
mechanizm persystencji.

**Rozwiązanie:**

```php
// src/Contracts/TableStateRepositoryInterface.php
interface TableStateRepositoryInterface
{
    public function save(string $tableId, array $identifier, array $state): void;
    public function load(string $tableId, array $identifier): ?array;
}

// src/Repositories/EloquentTableStateRepository.php
class EloquentTableStateRepository implements TableStateRepositoryInterface
{
    public function save(string $tableId, array $identifier, array $state): void
    {
        TableState::updateOrCreate(
            array_merge(['table_id' => $tableId], $identifier),
            ['state' => $state],
        );
    }

    public function load(string $tableId, array $identifier): ?array
    {
        return TableState::where('table_id', $tableId)
            ->where($identifier)
            ->first()?->state;
    }
}
```

W `LiveTableServiceProvider`:
```php
$this->app->bind(
    TableStateRepositoryInterface::class,
    EloquentTableStateRepository::class,
);
```

`BaseTable` używa `app(TableStateRepositoryInterface::class)` zamiast bezpośrednio `TableState`.

---

## 4.3 Rozbicie widoku Blade na `@include` partials

**Plik:** `resources/views/bootstrap/base-table.blade.php` (964 linie)

CLAUDE.md dopuszcza `@include` – to nie są komponenty anonimowe,
tylko wczytanie pliku do kontekstu rodzica. Wszystkie zmienne nadal
są dostępne bez przekazywania.

**Struktura po refaktorze:**

```
resources/views/bootstrap/
  base-table.blade.php          ← szkielet, @include partials
  partials/
    _topbar.blade.php           ← wyszukiwanie, akcje nagłówkowe, przyciski
    _filter-tags.blade.php      ← aktywne tagi filtrów
    _select-all-banner.blade.php ← banner "zaznaczono wszystkie X wierszy"
    _table-head.blade.php       ← thead: kolumny, drag&drop, sortowanie
    _table-body.blade.php       ← tbody: wiersze, sub-rows, empty state, sentinel
    _table-foot.blade.php       ← tfoot: sumy, liczby
    _pagination.blade.php       ← per-page selector, linki stron, "X-Y z Z"
    _modal-filters.blade.php    ← modal filtrów
    _modal-creating.blade.php   ← modal tworzenia rekordu
    _modal-editing.blade.php    ← modal edycji rekordu
    _modal-mass-edit.blade.php  ← modal edycji zbiorczej
```

`base-table.blade.php` po refaktorze:
```blade
<div wire:id="{{ $this->id }}" ...>
    @include('live-table::bootstrap.partials._topbar')
    @include('live-table::bootstrap.partials._filter-tags')
    @include('live-table::bootstrap.partials._select-all-banner')

    <table class="table ...">
        @include('live-table::bootstrap.partials._table-head')
        @include('live-table::bootstrap.partials._table-body')
        @include('live-table::bootstrap.partials._table-foot')
    </table>

    @include('live-table::bootstrap.partials._pagination')
    @include('live-table::bootstrap.partials._modal-filters')
    @include('live-table::bootstrap.partials._modal-creating')
    @include('live-table::bootstrap.partials._modal-editing')
    @include('live-table::bootstrap.partials._modal-mass-edit')
</div>
```

**Uwaga:** Zaktualizować `ServiceProvider::publish()` aby publikował
cały katalog `partials/` razem z głównym plikiem.

---

## 4.4 Fluent clone pattern – eliminacja ręcznego kopiowania konstruktora

**Pliki:** `src/Action.php`, `src/BulkAction.php`, `src/RowAction.php`

```php
// Teraz – podatne na błąd przy dodaniu nowego pola:
public function method(string $method): static
{
    return new static($this->label, $method, $this->href, $this->icon);
}
```

Przy dodaniu nowego parametru do konstruktora trzeba zaktualizować
**wszystkie** metody fluent – łatwo o pominięcie.

**Rozwiązanie (PHP 8.2 – zmiana `readonly` na `private`, używanie `clone`):**

```php
class Action
{
    private string $label;
    private string $method = '';
    private string $href   = '';
    private string $icon   = '';

    private function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    public function method(string $method): static
    {
        $clone         = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function href(string $href): static
    {
        $clone       = clone $this;
        $clone->href = $href;
        return $clone;
    }

    // ... gettery
    public function getLabel(): string { return $this->label; }
    public function getMethod(): string { return $this->method; }
}
```

Alternatywnie: PHP 8.3 `clone with` (gdy minimalna wersja PHP zostanie podniesiona).

---

## 4.5 `SubRows::fromQuery()` – lazy loading zamiast natychmiastowego zapytania

**Plik:** `src/SubRows.php:31-36`

```php
public static function fromQuery(Builder $query): static
{
    $instance->items = $query->get()->all(); // zapytanie w konstruktorze!
}
```

**Rozwiązanie:**

```php
class SubRows
{
    private array    $items   = [];
    private ?Builder $query   = null;
    private bool     $loaded  = false;

    public static function fromQuery(Builder $query): static
    {
        $instance        = new static();
        $instance->query = $query;
        return $instance;
    }

    public function getItems(): array
    {
        if (! $this->loaded && $this->query !== null) {
            $this->items  = $this->query->get()->all();
            $this->loaded = true;
        }
        return $this->items;
    }
}
```

---

## 4.6 `exportPdf()` – lepszy return type i guard clause

**Plik:** `src/BaseTable.php:826-833`

```php
public function exportPdf(): mixed  // zwraca null gdy $exportPdf = false
```

Publiczna metoda zwracająca `null` bez sygnalizacji błędu jest myląca.

**Rozwiązanie:**
```php
public function exportPdf(): \Symfony\Component\HttpFoundation\Response
{
    if (! $this->exportPdf) {
        abort(404);
    }

    $result = $this->generatePdf($this->getExportRows(), $this->visibleColumns());

    if ($result === null) {
        throw new \BadMethodCallException(
            'LiveTable: zaimplementuj generatePdf() w ' . static::class
        );
    }

    return $result;
}
```

---

## 4.7 Tailwind view – sprawdzić feature parity z Bootstrap

**Plik:** `resources/views/tailwind/base-table.blade.php`

Sprawdzić czy widok Tailwind zawiera wszystkie funkcje co Bootstrap:
- Sub-rows / expandable
- Infinite scroll
- Mass edit modal
- Default creating / editing modals
- Row actions (dropdown + icons mode)
- Aggregate footer

Jeśli nie – oznaczyć jako `<!-- WIP: not feature-complete -->` w pliku
lub dodać notatkę w README.

---

## 4.8 `$persistState` zmienić z `public` na `protected`

**Plik:** `src/BaseTable.php:40`

```php
public bool $persistState = false;
```

Publiczna właściwość Livewire może zostać zmieniona przez klienta przez
manipulację snapshotu (wyłączenie persystencji). Lepiej jako `protected`:

```php
protected bool $persistState = false;
```

> **Uwaga:** Sprawdzić czy Livewire 4 nie wymaga `public` dla reaktywności.
> Jeśli `protected` nie działa – dodać `#[Locked]` atrybut Livewire.

---

## 4.9 Ujednolicenie API `BulkAction` – dodać `->tooltip()`

**Plik:** `src/BulkAction.php`

`BulkAction` ma `$label` używany jako tooltip, ale brak oddzielnego `$tooltip`.
`RowAction` i `Action` mają `$label` jako tekst przycisku.
API jest niespójne.

**Rozwiązanie:**
```php
class BulkAction
{
    public function __construct(
        public readonly string $method,
        public readonly string $label,    // tekst widoczny przy hover/screen reader
        public readonly string $icon  = '',
        public readonly string $tooltip = '', // opcjonalny tooltip HTML
    ) {}
}
```

---

## 4.10 `InstallCommand` – sprawdzenie czy migracja już istnieje

**Plik:** `src/Commands/InstallCommand.php`

Przy ponownym uruchomieniu `php artisan live-table:install` może nadpisać
już zmodyfikowane migracje lub pytać o każdy plik z osobna.

**Rozwiązanie:**
```php
$migrationExists = collect(glob(database_path('migrations/*.php')))
    ->contains(fn($path) => str_contains($path, 'create_live_table_states_table'));

if ($migrationExists) {
    $this->info('Migracja live_table_states już istnieje – pomijam.');
} else {
    $this->callSilent('vendor:publish', ['--tag' => 'live-table-migrations']);
}
```

---

## 4.11 Dodać sekcję "Security" do README

Dodać sekcję w `README.md` wyjaśniającą:

```markdown
## Bezpieczeństwo

### Scopowanie zapytań do użytkownika

`baseQuery()` **musi** zawężać wyniki do bieżącego użytkownika:

```php
protected function baseQuery(): Builder
{
    return Order::where('user_id', auth()->id());
}
```

### Hasła i wrażliwe pola

Nie umieszczaj `password` w `$fillable` bez obsługi `beforeCreate()`:

```php
protected function beforeCreate(array &$data): void
{
    if (isset($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    }
}
```

### Autoryzacja akcji

```php
protected function authorizeAction(string $action, mixed $record = null): void
{
    $this->authorize($action, $record ?? $this->model);
}
```

### Pola dostępne w formularzach

Używaj `$creatableFields` żeby ograniczyć eksponowane pola:

```php
protected array $creatableFields = ['name', 'email', 'role'];
// is_admin, stripe_id i inne wrażliwe pola NIE będą dostępne
```
```

---

## 4.12 Dodać `CHANGELOG.md`

Przydatny przy wydaniach na Packagist. Format: [Keep a Changelog](https://keepachangelog.com/).

```markdown
# Changelog

## [Unreleased]

### Added
- Default actions (edit/delete modals)
- Default creating modal
- Mass edit modal
- Mass delete with confirmation
- Summary/count aggregate columns

## [0.1.0] - 2024-xx-xx
### Added
- Initial release
```