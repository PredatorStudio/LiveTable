<?php

namespace PredatorStudio\LiveTable\Concerns;

trait ManagesBulkActions
{
    public function selectAllFromQuery(): void
    {
        $this->selectAllQuery = true;
    }

    public function clearSelectAllQuery(): void
    {
        $this->selectAllQuery = false;
        $this->selected = [];
    }
}
