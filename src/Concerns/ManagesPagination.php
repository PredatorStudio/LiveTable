<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ManagesPagination
{
    private function paginateQuery(Builder $query, int $total): array
    {
        if ($this->perPage === 0) {
            $loadedRows = max($this->loadedRows, $this->infiniteChunkSize);
            $items = $query->limit($loadedRows)->get();

            return [
                'items' => $items,
                'lastPage' => 1,
                'from' => $total > 0 ? 1 : 0,
                'to' => $items->count(),
                'pages' => [],
                'allLoaded' => $loadedRows >= $total,
                'infiniteMode' => true,
            ];
        }

        $lastPage = max(1, (int) ceil($total / $this->perPage));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        $items = $query
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        return [
            'items' => $items,
            'lastPage' => $lastPage,
            'from' => $total > 0 ? ($this->page - 1) * $this->perPage + 1 : 0,
            'to' => min($total, $this->page * $this->perPage),
            'pages' => $this->buildPageLinks($lastPage),
            'allLoaded' => false,
            'infiniteMode' => false,
        ];
    }

    private function buildPageLinks(int $lastPage): array
    {
        if ($lastPage <= 1) {
            return [];
        }

        $current = $this->page;
        $keep = [1, $lastPage];

        for ($i = max(2, $current - 2); $i <= min($lastPage - 1, $current + 2); $i++) {
            $keep[] = $i;
        }

        sort($keep);
        $keep = array_unique($keep);

        $result = [];
        $prev = null;

        foreach ($keep as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $result[] = '...';
            }

            $result[] = $p;
            $prev = $p;
        }

        return $result;
    }
}