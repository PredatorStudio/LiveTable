<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesInfiniteScroll
{
    public function loadMore(): void
    {
        if ($this->perPage !== 0) {
            return;
        }

        $this->loadedRows += $this->infiniteChunkSize;
    }

    private function resetInfiniteScroll(): void
    {
        if ($this->perPage === 0) {
            $this->loadedRows = $this->infiniteChunkSize;
        }
    }
}