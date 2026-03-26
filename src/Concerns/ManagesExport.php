<?php

namespace PredatorStudio\LiveTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PredatorStudio\LiveTable\Column;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ManagesExport
{
    /**
     * Export visible columns to CSV using a memory-efficient cursor iteration.
     * Streams the response without loading all rows into memory at once.
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorizeAction('export');

        $columns = $this->visibleColumns();
        $query = $this->buildExportQuery();
        $filename = class_basename(static::class).'_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query, $columns) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, array_map(fn (Column $col) => $col->label, $columns));

            foreach ($query->cursor() as $row) {
                fputcsv($output, $this->rowToCsvArray($row, $columns));

                if ($this->expandable) {
                    $sub = $this->subRows($row);
                    foreach ($sub?->getItems() ?? [] as $subRow) {
                        fputcsv($output, $this->rowToCsvArray($subRow, $columns));
                    }
                }
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Override to implement PDF export with your preferred library.
     *
     * Example with dompdf:
     *   $html = view('your-pdf-view', compact('rows', 'columns'))->render();
     *   return response($pdf->loadHtml($html)->output(), 200, [
     *       'Content-Type'        => 'application/pdf',
     *       'Content-Disposition' => 'attachment; filename="export.pdf"',
     *   ]);
     *
     * @param  Column[]  $columns
     */
    protected function generatePdf(Collection $rows, array $columns): mixed
    {
        return null;
    }

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        if (! $this->exportPdf) {
            abort(404);
        }

        $this->authorizeAction('export');

        $result = $this->generatePdf($this->buildExportQuery()->get(), $this->visibleColumns());

        if ($result === null) {
            throw new \BadMethodCallException(
                'LiveTable: implement generatePdf() in '.static::class.' to enable PDF export.',
            );
        }

        return $result;
    }

    private function buildExportQuery(): Builder
    {
        $query = $this->buildQuery();

        if (! $this->selectAllQuery && ! empty($this->selected)) {
            $query->whereIn($this->primaryKey, $this->selected);
        }

        return $query;
    }

    private function rowToCsvArray(mixed $row, array $columns): array
    {
        return array_map(
            fn (Column $col) => $col->renderCellPlain($row),
            $columns,
        );
    }
}
