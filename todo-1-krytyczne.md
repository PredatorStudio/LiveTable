# LiveTable – Etap 1: Bezpieczeństwo (KRYTYCZNE)

> Priorytet: 🔴 Wykonać jako pierwsze
> Podejście: TDD – najpierw test, potem implementacja

---

## 1.1 Niebezpieczny `$sortBy` w `orderBy()` — brak walidacji w `render()`

**Plik:** `src/BaseTable.php:1264`

```php
// PROBLEM: $sortBy jest publiczną właściwością Livewire.
// Użytkownik może ją dowolnie ustawić przez manipulację stanu komponentu,
// z pominięciem metody sort(), która waliduje dozwolone kolumny.
$query->orderBy($this->sortBy, $this->sortDir);
```

Mimo że `sort()` waliduje kolumnę, publiczna property `$sortBy` może zostać
nadpisana bezpośrednio przez Livewire payload. Eloquent parametryzuje wartość,
ale **nazwa kolumny** trafia do SQL bez ucieczki, co grozi SQL injection.

**Test (najpierw):**
```php
it('render ignores sortBy that is not in sortable columns', function () {
    $table = stubTable();
    $table->sortBy = 'nonexistent_column; DROP TABLE users';
    // render() nie powinien wywołać orderBy z tą wartością
    // → brak wyjątku, query bez ORDER BY
});
```

**Rozwiązanie:** W `render()` przed `orderBy()`:
```php
$sortable = array_column(
    array_filter($this->cachedColumns(), fn(Column $c) => $c->sortable),
    'key',
);
if ($this->sortBy !== '' && in_array($this->sortBy, $sortable, true)) {
    $query->orderBy($this->sortBy, $this->sortDir);
}
```

---

## 1.2 Niebezpieczny `$sortDir` — brak walidacji wartości

**Plik:** `src/BaseTable.php:1264`

`$sortDir` jest publiczny. Można go ustawić na dowolny ciąg. PDO chroni przed
wstrzyknięciem wartości, ale defensywna walidacja jest konieczna.

**Test (najpierw):**
```php
it('render clamps sortDir to asc when invalid value is set', function () {
    $table = stubTable();
    $table->sortBy  = 'name';
    $table->sortDir = 'INVALID; --';
    // oczekujemy 'asc' w zapytaniu, nie wyjątku
});
```

**Rozwiązanie:** W `render()`, tuż przed `orderBy()`:
```php
$dir = $this->sortDir === 'desc' ? 'desc' : 'asc';
$query->orderBy($this->sortBy, $dir);
```

---

## 1.3 Brak autoryzacji w metodach destruktywnych

**Pliki:** `deleteRow()`, `massDelete()`, `massEditUpdate()`, `updateRecord()`

Metody wywołują `baseQuery()` do znalezienia rekordu, ale `baseQuery()` nie musi
być scopowany do zalogowanego użytkownika. Nie ma żadnego wbudowanego
mechanizmu autoryzacji. Atakujący może wywołać te metody z dowolnym ID.

**Test (najpierw):**
```php
it('deleteRow calls authorizeAction before deleting', function () {
    // stub z nadpisanym authorizeAction który rzuca wyjątek
    // oczekujemy że rekord NIE zostanie usunięty
});
```

**Rozwiązanie:**

Dodać pusty hook w `BaseTable`:
```php
/**
 * Override to add authorization checks.
 * Throw \Illuminate\Auth\Access\AuthorizationException to abort.
 *
 * @param  string  $action  'create' | 'update' | 'delete' | 'massEdit' | 'massDelete'
 * @param  mixed   $record  Model instance or null (for mass operations)
 */
protected function authorizeAction(string $action, mixed $record = null): void
{
    // domyślnie brak ograniczeń – przesłoń w podklasie
}
```

Wywołać w każdej destruktywnej metodzie:
```php
public function deleteRow(string $id): void
{
    // ...
    $record = $this->baseQuery()->where($this->primaryKey, $id)->firstOrFail();
    $this->authorizeAction('delete', $record);
    // ...
}
```

Dodać ostrzeżenie w README + docblock `baseQuery()`:
> ⚠️ `baseQuery()` MUSI zawężać wyniki do bieżącego użytkownika, np.:
> `return User::where('team_id', auth()->user()->team_id);`

---

## 1.4 Pole `password` w formularzu tworzenia bez haszowania

**Plik:** `src/BaseTable.php:609-633`

Gdy model ma `password` w `$fillable`, `creatingFields()` zwraca pole
`type: 'password'`. Wartość jest zapisywana **plaintext** do bazy.

**Test (najpierw):**
```php
it('createRecord throws when password field present and no beforeCreate override', function () {
    // stub z modelem mającym 'password' w $fillable
    // oczekujemy LogicException lub podobnego
});
```

**Rozwiązanie:**

W `createRecord()` i `updateRecord()` dodać guard:
```php
private function assertPasswordFieldHandled(array $data): void
{
    $passwordKeys = array_filter(
        array_keys($data),
        fn(string $k) => Str::is(['password', '*_password'], $k),
    );

    if (! empty($passwordKeys)) {
        throw new \LogicException(
            'LiveTable: pole "' . implode('", "', $passwordKeys) . '" zawiera hasło. '
            . 'Użyj beforeCreate() / beforeUpdate() do haszowania: '
            . '$data["password"] = Hash::make($data["password"]);'
        );
    }
}
```

Wywołać w `createRecord()` przed `$this->beforeCreate($data)`.
Można wyłączyć przez `protected bool $allowPlaintextPasswords = false;`
po świadomym przesłonięciu przez dewelopera.

---

## 1.5 Nadmiarowe wystawienie `$fillable` w modalach

**Plik:** `src/BaseTable.php:608`

`creatingFields()` eksponuje **wszystkie** pola z `$fillable` modelu,
w tym potencjalnie wrażliwe (`is_admin`, `stripe_id`, `email_verified_at`).

**Test (najpierw):**
```php
it('creatingFields respects $creatableFields whitelist', function () {
    // stub z $creatableFields = ['name', 'email']
    // model ma $fillable = ['name', 'email', 'is_admin']
    // oczekujemy że zwróci tylko name i email
});
```

**Rozwiązanie:**

Dodać w `BaseTable`:
```php
/**
 * Whitelist pól dozwolonych w modalu tworzenia.
 * Gdy puste – używa $fillable modelu (zachowanie domyślne).
 * Gdy ustawione – wyłącznie te pola są widoczne i zapisywalne.
 *
 * Przykład: protected array $creatableFields = ['name', 'email', 'role'];
 */
protected array $creatableFields = [];

/**
 * Whitelist pól dozwolonych w modalu edycji.
 * Gdy puste – fallback na $creatableFields, potem $fillable.
 */
protected array $editableFields = [];
```

W `creatingFields()` uwzględnić whitelist:
```php
$fillable = empty($this->creatableFields)
    ? $instance->getFillable()
    : array_intersect($instance->getFillable(), $this->creatableFields);
```

---

## 1.6 Brak ograniczenia rozmiaru `$selected` i `$activeFilters`

Publiczne właściwości Livewire mogą zostać wypełnione tysiącami wartości
przez manipulację payloadu → DoS lub bardzo wolne `WHERE IN`.

**Rozwiązanie:**

Dodać stałe konfiguracyjne w `config/live-table.php`:
```php
'max_selected'      => 10_000,
'max_filter_length' => 500,
```

W `toggleSelectRow()` i `selectRows()`:
```php
$limit = config('live-table.max_selected', 10_000);
if (count($this->selected) >= $limit) {
    return; // lub dispatch event z ostrzeżeniem
}
```

W `applyActiveFilters()` – trim każdej wartości filtra do max długości.

---

## 1.7 Uruchomić nowe testy przed mergem

Trzy pliki testów są untracked (niezcommitowane) na gałęzi `Default-Actions`:

```
tests/Unit/DefaultActionsTest.php
tests/Unit/DefaultCreatingTest.php
tests/Unit/MassEditTest.php
```

**Akcja:**
```bash
make test
# lub filtrując:
docker compose run --rm php ./vendor/bin/pest > /app/pest_out.txt 2>&1
cat pest_out.txt
```

Upewnić się że wszystkie testy przechodzą zanim zostanie wykonany merge do `main`.