<?php

namespace PredatorStudio\LiveTable;

use Closure;
use PredatorStudio\LiveTable\Cells\BadgeCell;
use PredatorStudio\LiveTable\Cells\Cell;
use PredatorStudio\LiveTable\Cells\CheckboxCell;
use PredatorStudio\LiveTable\Cells\DateCell;
use PredatorStudio\LiveTable\Cells\DateTimeCell;
use PredatorStudio\LiveTable\Cells\EditableCell;
use PredatorStudio\LiveTable\Contracts\EditableCellInterface;
use PredatorStudio\LiveTable\Cells\LinkCell;
use PredatorStudio\LiveTable\Cells\MoneyCell;
use PredatorStudio\LiveTable\Cells\NumberCell;
use PredatorStudio\LiveTable\Cells\SelectCell;
use PredatorStudio\LiveTable\Cells\TextCell;
use PredatorStudio\LiveTable\Cells\TimeCell;
use PredatorStudio\LiveTable\Enums\DateFormat;
use PredatorStudio\LiveTable\Enums\DateTimeFormat;
use PredatorStudio\LiveTable\Enums\MoneyFormat;
use PredatorStudio\LiveTable\Enums\TimeFormat;

class Column
{
    public bool $sortable = false;

    public bool $visible = true;

    public ?string $width = null;

    private ?Closure $formatter = null;

    private Cell $cell;

    public function __construct(
        public readonly string $key,
        public readonly string $label,
    )
    {
        $this->cell = new TextCell;
    }

    // -------------------------------------------------------------------------
    // Base factory
    // -------------------------------------------------------------------------

    public static function make(string $key, string $label): static
    {
        return new static($key, $label);
    }

    // -------------------------------------------------------------------------
    // Read-only cell factories
    // -------------------------------------------------------------------------

    public static function text(string $key, string $label): static
    {
        return static::make($key, $label);
    }

    public static function number(string $key, string $label, int $decimals = 0, string $prefix = '', string $suffix = ''): static
    {
        return static::make($key, $label)->cell(new NumberCell(decimals: $decimals, prefix: $prefix, suffix: $suffix));
    }

    public static function date(string $key, string $label, DateFormat|string $format = DateFormat::DMY): static
    {
        return static::make($key, $label)->cell(new DateCell($format));
    }

    public static function dateTime(string $key, string $label, DateTimeFormat|string $format = DateTimeFormat::DMY_HM): static
    {
        return static::make($key, $label)->cell(new DateTimeCell($format));
    }

    public static function time(string $key, string $label, TimeFormat|string $format = TimeFormat::HI): static
    {
        return static::make($key, $label)->cell(new TimeCell($format));
    }

    public static function money(string $key, string $label, MoneyFormat|string $format = MoneyFormat::SPACE_COMMA, string $currency = ''): static
    {
        return static::make($key, $label)->cell(new MoneyCell($format, $currency));
    }

    public static function link(string $key, string $label, Closure $urlResolver, ?Closure $labelResolver = null): static
    {
        return static::make($key, $label)->cell(new LinkCell($urlResolver, $labelResolver));
    }

    public static function badge(string $key, string $label, array $map = []): static
    {
        return static::make($key, $label)->cell(new BadgeCell($map));
    }

    public static function custom(string $key, string $label, Cell $cell): static
    {
        return static::make($key, $label)->cell($cell);
    }

    // -------------------------------------------------------------------------
    // Editable cell factories
    // -------------------------------------------------------------------------

    public static function select(string $key, string $label, SelectCell $selectCell): static
    {
        return static::make($key, $label)->cell($selectCell);
    }

    public static function checkbox(string $key, string $label): static
    {
        return static::make($key, $label)->cell(new CheckboxCell);
    }

    // -------------------------------------------------------------------------
    // Fluent modifiers
    // -------------------------------------------------------------------------

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;

        return $this;
    }

    public function hidden(): static
    {
        $this->visible = false;

        return $this;
    }

    public function width(?string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Custom cell renderer (highest priority). Receives ($row, $value) → HTML string.
     */
    public function format(Closure $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Assign a Cell instance directly (escape hatch).
     */
    public function cell(Cell $cell): static
    {
        if ($cell instanceof EditableCellInterface) {
            $cell->setColumnKey($this->key);
        }

        $this->cell = $cell;

        return $this;
    }

    public function getCell(): Cell
    {
        return $this->cell;
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * Render a plain-text value for CSV export (no HTML tags, no editable widgets).
     */
    public function renderCellPlain(mixed $row): string
    {
        $value = data_get($row, $this->key);

        if ($this->formatter !== null) {
            return strip_tags(html_entity_decode((string)($this->formatter)($row, $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $this->cell->renderPlain($row, $value);
    }

    /**
     * @param mixed $row The current data row.
     * @param string $primaryKey The primary key field name (needed for editable cells).
     */
    public function renderCell(mixed $row, string $primaryKey = ''): string
    {
        $value = data_get($row, $this->key);

        if ($this->formatter !== null) {
            return (string)($this->formatter)($row, $value);
        }

        if ($this->cell instanceof EditableCellInterface) {
            $rowId = $primaryKey !== '' ? (string)data_get($row, $primaryKey) : '';

            return $this->cell->renderEditable($row, $value, $rowId);
        }

        return $this->cell->render($row, $value);
    }
}
