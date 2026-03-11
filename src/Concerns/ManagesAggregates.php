<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Support\Collection;
use PredatorStudio\LiveTable\Enums\AggregateScope;

trait ManagesAggregates
{
    /**
     * Compute footer aggregates (sums and counts) based on $aggregateScope.
     * For scope ALL: executes a single selectRaw() query instead of N separate queries.
     *
     * @param  Collection  $pageItems  Already fetched current-page rows.
     * @return array{0: array<string,mixed>, 1: array<string,int>}  [sumData, countData]
     */
    private function computeAggregates(Collection $pageItems): array
    {
        $sumData   = [];
        $countData = [];

        if (empty($this->sumColumns) && empty($this->countColumns)) {
            return [$sumData, $countData];
        }

        if ($this->aggregateScope === AggregateScope::PAGE) {
            foreach ($this->sumColumns as $col) {
                $sumData[$col] = $pageItems->sum($col);
            }
            foreach ($this->countColumns as $col) {
                $countData[$col] = $pageItems->whereNotNull($col)->count();
            }

            return [$sumData, $countData];
        }

        // ALL scope – single query via selectRaw()
        $selects = [];
        foreach ($this->sumColumns as $col) {
            $selects[] = 'SUM(' . $this->quoteColumn($col) . ') as __sum_' . $col;
        }
        foreach ($this->countColumns as $col) {
            $selects[] = 'COUNT(' . $this->quoteColumn($col) . ') as __count_' . $col;
        }

        $row = $this->buildQuery()->selectRaw(implode(', ', $selects))->first();

        foreach ($this->sumColumns as $col) {
            $sumData[$col] = $row ? ($row->{'__sum_' . $col} ?? 0) : 0;
        }
        foreach ($this->countColumns as $col) {
            $countData[$col] = $row ? ($row->{'__count_' . $col} ?? 0) : 0;
        }

        return [$sumData, $countData];
    }

    /**
     * Validate and quote a column name for use in raw SQL.
     * Prevents SQL injection through $sumColumns / $countColumns.
     */
    private function quoteColumn(string $col): string
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $col)) {
            throw new \InvalidArgumentException("Nieprawidłowa nazwa kolumny: {$col}");
        }

        return '`' . str_replace('`', '', $col) . '`';
    }
}