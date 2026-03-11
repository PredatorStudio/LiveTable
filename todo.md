# LiveTable – Plan zadań

> Analiza: 2026-03-11 | Gałąź: `Default-Actions`
> Podejście: SOLID + TDD (Red → Green → Refactor) + Security + Optimization

Zadania podzielone na 4 etapy wykonywane kolejno.
Każdy etap stosuje TDD: najpierw test, potem implementacja.

---

## Etapy

| Plik | Priorytet | Zakres | Nakład |
|------|-----------|--------|--------|
| [todo-1-krytyczne.md](todo-1-krytyczne.md) | 🔴 Krytyczny | Bezpieczeństwo – SQL injection, autoryzacja, hasła | mały–średni |
| [todo-2-wysoki.md](todo-2-wysoki.md) | 🟠 Wysoki | Refaktoring SRP (traity), testy Feature, optymalizacja zapytań | duży |
| [todo-3-sredni.md](todo-3-sredni.md) | 🟡 Średni | Type safety, eliminacja duplikacji, UX optymalizacje | średni |
| [todo-4-niski.md](todo-4-niski.md) | 🟢 Niski | Architektura DIP, partials Blade, clone pattern, porządki | duży |

---

## Etap 1 – Bezpieczeństwo 🔴 [`todo-1-krytyczne.md`](todo-1-krytyczne.md)

- **1.1** Walidacja `$sortBy` w `render()` – kolumna bez whitelist trafia do SQL
- **1.2** Walidacja `$sortDir` – clamp do `'asc'|'desc'`
- **1.3** Hook `authorizeAction()` + dokumentacja scopowania `baseQuery()`
- **1.4** Guard przed plaintext `password` w `createRecord()`
- **1.5** Whitelist `$creatableFields` / `$editableFields` (ograniczenie `$fillable`)
- **1.6** Limit rozmiaru `$selected` i `$activeFilters` (DoS)
- **1.7** Uruchomić 3 nowe testy (untracked) przed mergem

---

## Etap 2 – Priorytet Wysoki 🟠 [`todo-2-wysoki.md`](todo-2-wysoki.md)

- **2.1** [SRP] Podział `BaseTable` (1420 linii) na 11 traitów w `src/Concerns/`
- **2.2** Aggregaty – jedno `selectRaw()` zamiast N+M zapytań
- **2.3** Eksport CSV – `cursor()` zamiast `get()` (pamięć)
- **2.4** Testy Feature – katalog `tests/Feature/` (Livewire integration)
- **2.5** Testy bezpieczeństwa – `SecurityTest.php`
- **2.6** Testy Cell rendering – `tests/Unit/Cells/`
- **2.7** Testy `resolveFieldType()` / `typeFromSchema()`

---

## Etap 3 – Priorytet Średni 🟡 [`todo-3-sredni.md`](todo-3-sredni.md)

- **3.1** `FilterType` enum zamiast magicznego stringa
- **3.2** `?string $model = null` zamiast `$model = ''`
- **3.3** Ekstrakcja `hasModel()` – eliminacja 6+ duplikacji
- **3.4** `FieldDefinition` value object zamiast `array{key, label, type}`
- **3.5** Ikony SVG `defaultActions` jako protected properties
- **3.6** Rozbicie `render()` na `buildPaginationData()` itd.
- **3.7** `preloadSubRows()` hook (N+1 w expandable)
- **3.8** `staticRowActions()` dla akcji niezależnych od wiersza
- **3.9** Limit `$selected` widoczny w UI

---

## Etap 4 – Priorytet Niski 🟢 [`todo-4-niski.md`](todo-4-niski.md)

- **4.1** Interfejsy PHP (`src/Contracts/` – `CellInterface`, `EditableCellInterface`)
- **4.2** [DIP] `TableStateRepositoryInterface` + `EloquentTableStateRepository`
- **4.3** Rozbicie widoku Blade (964 linii) na `@include` partials
- **4.4** Fluent clone pattern w `Action`, `BulkAction`, `RowAction`
- **4.5** `SubRows::fromQuery()` – lazy loading
- **4.6** `exportPdf()` – poprawny return type i guard clause
- **4.7** Tailwind view – sprawdzić feature parity z Bootstrap
- **4.8** `$persistState` → `protected` (lub `#[Locked]`)
- **4.9** Ujednolicenie API `BulkAction` – dodać `->tooltip()`
- **4.10** `InstallCommand` – sprawdzanie istniejących migracji
- **4.11** README – sekcja "Security"
- **4.12** `CHANGELOG.md`

---

## Uwaga o TDD

**TDD (Test-Driven Development)** to **metodologia** tworzenia oprogramowania,
nie tylko "metodyka". Cykl:

```
1. RED   – napisz test który nie przechodzi
2. GREEN – napisz minimalny kod który test przechodzi
3. REFACTOR – popraw kod bez łamania testów
```

Każde zadanie w etapach 1–3 zawiera przykładowy test do napisania *przed* implementacją.