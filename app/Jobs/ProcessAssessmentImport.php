<?php

namespace App\Jobs;

use App\Models\SlaveMasterData;
use App\Models\AssessmentMasterData;
use App\Models\LicenseeTemplate;
use App\Models\LicenseeTemplateKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessAssessmentImport implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue, Queueable, SerializesModels;

    public $assessmentId;
    public $licenseeId;

    public function __construct($assessmentId, $licenseeId)
    {
        $this->assessmentId = $assessmentId;
        $this->licenseeId = $licenseeId;
    }

    public function handle()
    {
        $rows = SlaveMasterData::where('assessment_id', $this->assessmentId)
            ->where('status', 'pending')
            ->chunk(100, function ($chunk) {
                foreach ($chunk as $slave) {
                    try {
                        $headers = is_array($slave->headers) ? $slave->headers : json_decode($slave->headers, true);
                        $rowData = is_array($slave->row_data) ? $slave->row_data : json_decode($slave->row_data, true);

                        
                        DB::transaction(function () use ($headers, $rowData, $slave) {
                            foreach ($rowData as $col => $value) {
                                $templateKey = LicenseeTemplateKey::where('short_code', $headers[$col])->first();

                                if ($templateKey) {
                                    AssessmentMasterData::create([
                                        'licensee_id' => $slave->licensee_id,
                                        'assessment_id' => $slave->assessment_id,
                                        'template_sheet_id  ' => $slave->sheet->id,
                                        'template_key_id' => $templateKey->id,
                                        'template_key_value' => $value,
                                        'type' => $templateKey->type,
                                        'entry_counter' => $slave->row_index,
                                    ]);
                                }
                            }
                            $slave->update(['status' => 'processed']);
                        });
                    } catch (Throwable $e) {
                        $slave->update([
                            'status' => 'failed',
                            'processing_message' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}


//php artisan queue:work
