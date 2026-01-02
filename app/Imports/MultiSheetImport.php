<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\DB;

class MultiSheetImport implements WithMultipleSheets
{
    public $assessmentId;
    public $licenseeId;
    public $sheetMapping;

    public $errorsPerSheet = [];
    public $namePerSheet = [];
    public $canProceed = true;

    public function __construct($assessmentId, $licenseeId, $sheetMapping)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId   = $licenseeId;
        $this->sheetMapping = $sheetMapping;
    }

    public function sheets(): array
    {
        $handlers = [];

        foreach ($this->sheetMapping as $sheetName => $templateId) {
            $handlers[$sheetName] = new SingleSheetImport(
                $this->assessmentId,
                $this->licenseeId,
                $sheetName,
                $templateId,
                $this
            );
        }

        return $handlers;
    }
}
