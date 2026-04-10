<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Support\Collection;
use PredatorStudio\LiveTable\RowAction;

trait ManagesRowActions
{
    /**
     * Build sub-row and sub-row-actions maps for the current page.
     * Returns empty maps when $expandable is false (no queries executed).
     *
     * @return array{subRowsMap: array, subRowActionsMap: array}
     */
    private function buildSubRowData(Collection $items): array
    {
        if (! $this->expandable) {
            return ['subRowsMap' => [], 'subRowActionsMap' => []];
        }

        $subRowsMap = [];
        $subRowActionsMap = [];

        $this->preloadSubRows($items);

        foreach ($items as $item) {
            $key = (string) data_get($item, $this->primaryKey);
            $sub = $this->subRows($item);
            $subItems = $sub ? $sub->getItems() : [];

            $subRowsMap[$key] = $subItems;

            if ($this->subRowsHasActions) {
                foreach ($subItems as $subRow) {
                    $subKey = (string) data_get($subRow, $this->subRowPrimaryKey);
                    $subRowActionsMap[$key][$subKey] = array_map(
                        fn (RowAction $a) => $this->serializeRowAction($a, $subRow),
                        $this->subRowActions($subRow),
                    );
                }
            }
        }

        return compact('subRowsMap', 'subRowActionsMap');
    }

    /**
     * Build the per-row actions map for the current page.
     * Merges static actions, per-row actions and default edit/delete actions.
     *
     * @return array{rowActionsMap: array, hasRowActions: bool}
     */
    private function buildRowActionsData(Collection $items, bool $canEdit, bool $canDelete): array
    {
        $staticActions = $this->staticRowActions();
        $hasRowActions = false;
        $rowActionsMap = [];

        foreach ($items as $item) {
            $key = (string) data_get($item, $this->primaryKey);
            $actions = array_merge($staticActions, $this->rowActions($item));

            if ($canEdit) {
                $actions[] = RowAction::make('Edytuj')
                    ->method('openEditingModal')
                    ->icon($this->defaultEditIcon);
            }

            if ($canDelete) {
                $actions[] = RowAction::make('Usuń')
                    ->method('deleteRow')
                    ->icon($this->defaultDeleteIcon)
                    ->confirm('Czy na pewno chcesz usunąć ten rekord?');
            }

            if (! empty($actions)) {
                $hasRowActions = true;
            }

            $rowActionsMap[$key] = array_map(
                fn (RowAction $a) => $this->serializeRowAction($a, $item),
                $actions,
            );
        }

        return compact('rowActionsMap', 'hasRowActions');
    }

    /**
     * Serialize a RowAction to a plain object for the Blade view.
     * Used by both buildRowActionsData() and buildSubRowData().
     */
    private function serializeRowAction(RowAction $action, mixed $row): object
    {
        return (object) [
            'label' => $action->label,
            'icon' => $action->icon,
            'href' => $action->resolveHref($row),
            'method' => $action->method,
            'confirm' => $action->confirm,
        ];
    }
}