<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports a list of skipped rows from a CSV import as an Excel file.
 *
 * Each row in the output contains:
 *   - Row Number (the original row index in the uploaded file, 1-based)
 *   - Column     (the short_code / column name that failed)
 *   - Error      (the human-readable validation message)
 *
 * Usage:
 *   return Excel::download(new CsvErrorExport($skippedRows), 'errors.xlsx');
 *
 * Where $skippedRows is an array of:
 *   [['row_index' => int, 'errors' => ['col' => ['msg1', ...], ...]], ...]
 */
class CsvErrorExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly array $skippedRows) {}

    public function headings(): array
    {
        return ['Row Number (in original file)', 'Column', 'Error'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->skippedRows as $item) {
            $errors = $item['errors'] ?? [];

            if (empty($errors)) {
                // Row was marked as skipped but has no detailed error message
                $rows[] = [
                    $item['row_index'],
                    '—',
                    'Row contained validation errors (no detail available)',
                ];
                continue;
            }

            foreach ($errors as $column => $messages) {
                foreach ((array) $messages as $message) {
                    $rows[] = [
                        $item['row_index'],
                        $column,
                        $message,
                    ];
                }
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Bold header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
