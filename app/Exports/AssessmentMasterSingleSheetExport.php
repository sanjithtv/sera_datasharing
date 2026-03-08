<?php

namespace App\Exports;

use App\Models\AssessmentMasterData;
use App\Models\LicenseeTemplateSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\DB;

class AssessmentMasterSingleSheetExport implements FromCollection, WithTitle, WithHeadings
{

    protected $assessmentId;
    protected $sheet;
    protected $keyMap;

    public function __construct($assessmentId, $sheet)
    {
        $this->assessmentId = $assessmentId;
        $this->sheet = $sheet;
        
        // Pre-build key map
        $this->keyMap = [];
        foreach ($this->sheet->keys as $index => $key) {
            $this->keyMap[$key->id] = $index;
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headers = [];
        foreach ($this->sheet->keys as $key) {
            $headers[] = $key->short_code;
        }
        return $headers;
    }

    /**
     * @return LazyCollection
     */
    public function collection()
    {
        return LazyCollection::make(function () {
            $query = DB::table('sr_licensee_assessment_master_data')
                ->where('assessment_id', $this->assessmentId)
                ->where('template_sheet_id', $this->sheet->id)
                ->orderBy('entry_counter');

            $currentRowIndex = -1;
            $currentRow = [];
            $columnCount = count($this->keyMap);

            foreach ($query->cursor() as $entry) {
                if ($entry->entry_counter !== $currentRowIndex) {
                    if ($currentRowIndex !== -1) {
                        yield $currentRow;
                    }
                    $currentRowIndex = $entry->entry_counter;
                    $currentRow = array_fill(0, $columnCount, null);
                }

                if (isset($this->keyMap[$entry->template_key_id])) {
                    $idx = $this->keyMap[$entry->template_key_id];
                    $currentRow[$idx] = $entry->template_key_value;
                }
            }

            if ($currentRowIndex !== -1) {
                yield $currentRow;
            }
        });
    }

    public function title(): string
    {
        return $this->sheet->sheet_name ?? 'Sheet';
    }
}
