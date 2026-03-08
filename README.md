# LiveTable

Interaktywny komponent tabeli danych dla Laravela oparty na **Livewire 4**.
Sortowanie, filtrowanie, paginacja, widoczność kolumn, drag & drop, edycja inline – bez własnego JavaScriptu.

## Wymagania

| Zależność | Wersja |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 |
| Livewire | ^4.0 |
| Bootstrap | 5.x |
| Alpine.js | 3.x |

Bootstrap 5 i Alpine.js muszą być załadowane przez aplikację hosta.

## Instalacja

```bash
composer require predatorstudio/live-table
```

Pakiet rejestruje się automatycznie przez Laravel Package Discovery.
Opcjonalnie opublikuj widoki, aby je dostosować:

```bash
php artisan vendor:publish --tag=live-table-views
```

## Szybki start

### 1. Utwórz komponent

```bash
php artisan make:livewire UsersTable
```

### 2. Rozszerz `BaseTable`

```php
<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PredatorStudio\LiveTable\BaseTable;
use PredatorStudio\LiveTable\Column;
use PredatorStudio\LiveTable\Filter;
use PredatorStudio\LiveTable\Action;
use PredatorStudio\LiveTable\BulkAction;

class UsersTable extends BaseTable
{
    protected function baseQuery(): Builder
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'ID')->sortable(),
            Column::make('name', 'Imię i nazwisko')->sortable(),
            Column::make('email', 'E-mail')->sortable(),
            Column::date('created_at', 'Data rejestracji')->sortable(),
        ];
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
```

### 3. Umieść w widoku Blade

```blade
<livewire:users-table />
```

---

## Kolumny (`Column`)

### Typy kolumn

| Metoda fabryczna | Opis |
|---|---|
| `Column::make($key, $label)` | Tekst (domyślnie) |
| `Column::text($key, $label)` | Jawny tekst |
| `Column::number($key, $label, $decimals, $prefix, $suffix)` | Liczba z opcjonalnym formatowaniem |
| `Column::date($key, $label, $format)` | Data (domyślnie `d.m.Y`) |
| `Column::dateTime($key, $label, $format)` | Data i czas (domyślnie `d.m.Y H:i`) |
| `Column::time($key, $label, $format)` | Czas (domyślnie `H:i`) |
| `Column::money($key, $label, $format, $currency)` | Kwota pieniężna |
| `Column::link($key, $label, $urlResolver, $labelResolver)` | Klikalny link |
| `Column::badge($key, $label, $map)` | Kolorowy badge |
| `Column::checkbox($key, $label)` | Edytowalny checkbox (inline) |
| `Column::select($key, $label, $selectCell)` | Edytowalny select (inline) |
| `Column::custom($key, $label, $cell)` | Własna implementacja `Cell` |

### Modyfikatory (fluent API)

```php
Column::make('status', 'Status')
    ->sortable()          // włącza sortowanie po kliknięciu nagłówka
    ->hidden()            // domyślnie ukryta (można odkryć przez panel kolumn)
    ->format(fn($row, $value) => '<strong>' . e($value) . '</strong>');
```

### Przykład – badge ze statusem

```php
Column::badge('status', 'Status', [
    'active'   => 'success',
    'inactive' => 'secondary',
    'banned'   => 'danger',
])->sortable(),
```

### Przykład – link

```php
Column::link('name', 'Imię', fn($row) => route('users.show', $row->id)),
```

---

## Filtry (`Filter`)

Zdefiniuj filtry w metodzie `filters()` i zastosuj je w `applyFilters()`:

```php
public function filters(): array
{
    return [
        Filter::text('name', 'Imię'),
        Filter::select('status', 'Status', [
            'active'   => 'Aktywny',
            'inactive' => 'Nieaktywny',
        ]),
        Filter::date('created_at', 'Data rejestracji'),
    ];
}

protected function applyFilters(Builder $query): Builder
{
    if (!empty($this->activeFilters['name'])) {
        $query->where('name', 'like', '%' . $this->activeFilters['name'] . '%');
    }

    if (!empty($this->activeFilters['status'])) {
        $query->where('status', $this->activeFilters['status']);
    }

    if (!empty($this->activeFilters['created_at'])) {
        $query->whereDate('created_at', $this->activeFilters['created_at']);
    }

    return $query;
}
```

Filtry są wyświetlane w modalnym oknie dialogowym.

---

## Akcje nagłówkowe (`Action`)

Przyciski wyświetlane w prawym górnym rogu tabeli:

```php
public function headerActions(): array
{
    return [
        Action::make('Dodaj użytkownika')
            ->href(route('users.create'))
            ->icon('<svg>...</svg>'),

        Action::make('Eksportuj')
            ->method('exportCsv'),
    ];
}
```

---

## Akcje zbiorcze (`BulkAction`)

Akcje na zaznaczonych wierszach. Włącz selekcję przez `$selectable = true`:

```php
protected bool $selectable = true;

public function bulkActions(): array
{
    return [
        BulkAction::make('deleteSelected', 'Usuń zaznaczone')
            ->icon('<svg>...</svg>'),
    ];
}

public function deleteSelected(): void
{
    User::whereIn('id', $this->selected)->delete();
    $this->selected = [];
}
```

Zaznaczone ID wiersza są dostępne w `$this->selected` (tablica stringów).

---

## Konfiguracja komponentu

Właściwości chronione, które można nadpisać w subklasie:

```php
protected bool   $selectable        = false;  // checkbox przy każdym wierszu
protected string $primaryKey        = 'id';   // klucz używany do selekcji
protected bool   $displaySearch     = true;   // pole wyszukiwania
protected bool   $displayColumnList = true;   // przycisk zarządzania kolumnami
```

---

## Edycja inline

Kolumny `checkbox` i `select` umożliwiają edycję bezpośrednio w tabeli.
Po zmianie wartości wywoływana jest metoda `updateCell()` w `BaseTable`, która przez interfejs `EditableCell` deleguje zapis do modelu.

Przykład `SelectCell`:

```php
use PredatorStudio\LiveTable\Cells\SelectCell;

Column::select('status', 'Status', new class extends SelectCell {
    protected array $options = [
        'active'   => 'Aktywny',
        'inactive' => 'Nieaktywny',
    ];

    public function update(mixed $row, mixed $value): void
    {
        $row->update(['status' => $value]);
    }
}),
```

---

## Jak działa pipeline danych

```
baseQuery()
    ↓
applySearch()   ← gdy pole wyszukiwania nie jest puste
    ↓
applyFilters()  ← gdy są aktywne filtry
    ↓
orderBy()       ← gdy wybrano sortowanie
    ↓
offset/limit    ← paginacja
    ↓
render()        → widok Blade
```

Każde zdarzenie Livewire (wpisanie w wyszukiwarce, kliknięcie sortowania, zmiana strony) uruchamia cały pipeline od nowa, gwarantując spójny stan.

---

## Struktura plików pakietu

```
src/
├── BaseTable.php               # Abstrakcyjny komponent Livewire
├── Column.php                  # Definicja kolumny, fluent API
├── Filter.php                  # Definicja filtra
├── Action.php                  # Akcja nagłówkowa
├── BulkAction.php              # Akcja zbiorcza
├── LiveTableServiceProvider.php
├── Cells/
│   ├── Cell.php                # Abstrakcja komórki (read-only)
│   ├── EditableCell.php        # Abstrakcja komórki edytowalnej
│   ├── TextCell.php
│   ├── NumberCell.php
│   ├── DateCell.php
│   ├── DateTimeCell.php
│   ├── TimeCell.php
│   ├── MoneyCell.php
│   ├── LinkCell.php
│   ├── BadgeCell.php
│   ├── CheckboxCell.php
│   └── SelectCell.php
└── Enums/
    ├── DateFormat.php
    ├── DateTimeFormat.php
    ├── TimeFormat.php
    └── MoneyFormat.php
resources/views/
├── bootstrap/base-table.blade.php  # Szablon Bootstrap 5
└── tailwind/base-table.blade.php   # Szablon Tailwind CSS
```

---

## Testy

```bash
./vendor/bin/pest
```

Framework testowy: **Pest PHP**.
Testy jednostkowe w `tests/Unit/`, testy integracyjne w `tests/Feature/`.

---

## Licencja

MIT © [Predator Studio](https://predatorstudio.pl)
