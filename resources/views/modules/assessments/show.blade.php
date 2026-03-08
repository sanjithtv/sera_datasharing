@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.assessments')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('css')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.assessments')
        @endslot
        @slot('title')
            @lang('translation.overview')
        @endslot
@endcomponent

{{-- ── PARSED: background job finished, awaiting user confirmation ── --}}
@if($assessment->status === 'parsed')
    @php
        $stagedCount = \App\Models\SlaveMasterData::where('assessment_id', $assessment->id)->count();
    @endphp
    <div class="alert alert-success border-success d-flex align-items-center justify-content-between mb-3" role="alert">
        <div>
            <i class="ri-checkbox-circle-line fs-5 me-2"></i>
            <strong>{{ __('CSV import ready!') }}</strong>
            {{ number_format($stagedCount) }} {{ __('rows have been staged and are waiting for your confirmation.') }}
        </div>
        <a href="{{ route('assessments.review', $assessment->id) }}" class="btn btn-success btn-sm ms-3 text-nowrap">
            <i class="ri-eye-line me-1"></i> {{ __('Review & Confirm Import') }}
        </a>
    </div>
@endif

{{-- ── PROCESSING: background job still running ── --}}
@if($assessment->status === 'processing')
    <div class="alert alert-warning border-warning mb-3" role="alert">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                <strong>{{ __('Step 1: Staging data…') }}</strong>
                {{ __('Preparing file for review.') }}
            </div>
            <a href="{{ route('assessments.show', $assessment->id) }}" class="btn btn-warning btn-sm ms-3 text-nowrap">
                <i class="ri-refresh-line me-1"></i> {{ __('Refresh') }}
            </a>
        </div>
        
        <div class="progress animated-progress custom-progress progress-label" style="height: 20px;">
            <div id="processing-progress-bar" class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div class="label text-dark">0%</div>
            </div>
        </div>
        <div class="mt-1 small text-muted text-end">
            {{ __('Processed') }} <span id="processed-count-step1">0</span> {{ __('of') }} <span id="total-count-step1">...</span> {{ __('rows') }}
        </div>
    </div>

    <script>
        function updateProcessingProgress() {
            fetch('{{ route('assessments.progress', $assessment->id) }}')
                .then(response => response.json())
                .then(data => {
                    const progressBar = document.getElementById('processing-progress-bar');
                    if (!progressBar) return;

                    const label = progressBar.querySelector('.label');
                    const processedCount = document.getElementById('processed-count-step1');
                    const totalCount = document.getElementById('total-count-step1');

                    const percent = data.percentage || 0;
                    progressBar.style.width = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent);
                    label.innerText = percent + '%';

                    processedCount.innerText = data.processed_rows.toLocaleString();
                    totalCount.innerText = (data.total_rows > 0) ? data.total_rows.toLocaleString() : '...';
                    
                    if (data.status !== 'processing') {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }
        setInterval(updateProcessingProgress, 2000);
        updateProcessingProgress();
    </script>
@endif

{{-- ── COMMITTING: final background data move running ── --}}
@if($assessment->status === 'committing')
    <div class="alert alert-info border-info mb-3" role="alert">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                <strong>{{ __('Step 2: Finalizing import…') }}</strong>
                {{ __('Moving data to production storage.') }}
            </div>
            <a href="{{ route('assessments.show', $assessment->id) }}" class="btn btn-info btn-sm ms-3 text-nowrap">
                <i class="ri-refresh-line me-1"></i> {{ __('Refresh') }}
            </a>
        </div>
        
        <div class="progress animated-progress custom-progress progress-label" style="height: 20px;">
            <div id="finalize-progress-bar" class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div class="label text-white">0%</div>
            </div>
        </div>
        <div class="mt-1 small text-muted text-end">
            {{ __('Moved') }} <span id="finalized-count">0</span> {{ __('of') }} <span id="total-count-final">...</span> {{ __('rows') }}
            <span id="duplicate-warning" class="ms-2 text-warning d-none">
                (<span id="duplicate-count-final">0</span> {{ __('duplicates skipped') }})
            </span>
        </div>
    </div>

    <script>
        function updateFinalizeProgress() {
            fetch('{{ route('assessments.progress', $assessment->id) }}')
                .then(response => response.json())
                .then(data => {
                    const progressBar = document.getElementById('finalize-progress-bar');
                    if (!progressBar) return;

                    const label = progressBar.querySelector('.label');
                    const finalizedCount = document.getElementById('finalized-count');
                    const totalCount = document.getElementById('total-count-final');

                    const percent = data.percentage || 0;
                    progressBar.style.width = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent);
                    label.innerText = percent + '%';

                    finalizedCount.innerText = data.finalized_rows.toLocaleString();
                    totalCount.innerText = (data.total_rows > 0) ? data.total_rows.toLocaleString() : '...';
                    
                    const dupWarning = document.getElementById('duplicate-warning');
                    const dupCount = document.getElementById('duplicate-count-final');
                    if (data.duplicate_rows > 0) {
                        dupWarning.classList.remove('d-none');
                        dupCount.innerText = data.duplicate_rows.toLocaleString();
                    }
                    
                    if (data.status === 'completed') {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }
        setInterval(updateFinalizeProgress, 2000);
        updateFinalizeProgress();
    </script>
@endif

<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <h4 class="mb-sm-1 font-size-18">@lang('translation.assessments')</h4>
            <div class="card" id="companyList">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><a href="{{ route('assessments.index') }}" class="btn btn-secondary btn-sm">{{ __('← Back to List') }}</a></div>
                        <div class="col-md-6 text-end">
                            @if(!in_array($assessment->status, ['completed','archived']))   
                            <form action="{{ route('assessments.destroy', $assessment->id) }}" method="POST" style="display:inline" >
                                @csrf @method('DELETE')
                                <a href="#" class="remove-item-btn btn btn-sm btn-warning" onclick="if(confirm('Delete this assessment?')) { this.closest('form').submit(); } return false;"><i class="ri-delete-bin-fill align-bottom text-muted"></i> @lang('translation.delete')</a>
                            </form>
                            @endif
                        </div>
                    </div>        
                     <br>
                    <!-- Section 1: Assessment Basic Details -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">{{ __('Basic Information') }}</div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4"><strong>{{ __('Licensee:') }}</strong></div>
                <div class="col-md-8">{{ $assessment->licensee->name_en ?? '—' }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>{{ __('Template:') }}</strong></div>
                <div class="col-md-8">
                    {{ $assessment->template->licensee->name_en ?? '' }} -
                    {{ $assessment->template->subfolder->name_en ?? '' }} (v{{ $assessment->template->version }})
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>{{ __('Assessment Date:') }}</strong></div>
                <div class="col-md-8">{{ \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>{{ __('Status:') }}</strong></div>
                <div class="col-md-8">
                    <span class="badge bg-{{ $assessment->status === 'completed' ? 'success' : ($assessment->status === 'in_progress' ? 'warning' : 'secondary') }}">
                        {{ __(ucfirst($assessment->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

   <!-- SECTION 2: Master Data (Grouped by Entry Counter) -->
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <span class="fw-bold">{{ __('Assessment Master Data') }}</span>

    <div class="btn-group">
        
        <!-- Optional Dropdown for More Options -->
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                {{ __('Actions') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('assessments.upload.form', $assessment->id) }}">
                        <i class="bi bi-download me-2 text-info"></i>{{ __('Excel Upload') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('assessments.form', $assessment->id) }}">
                        <i class="bi bi-download me-2 text-info"></i>{{ __('Manual Entry') }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger" id="clearDataBtn" href="{{ route('assessments.clearData', $assessment->id) }}">
                    <i class="bi bi-trash me-2"></i>{{ __('Clear All Data') }}
                    </a>
                </li>
                @if($sheetIds >0)
                <hr>
                <li>
                    <a class="dropdown-item" id="clearDataBtn" href="{{ route('assessments.export.master', $assessment->id) }}">
                    <i class="bi bi-trash me-2 "></i>{{ __('Export Data') }}
                    </a>
                </li>
                @endif

                @if(($assessment->duplicate_rows ?? 0) > 0)
                <li>
                    <div class="dropdown-item text-secondary">
                        <i class="bi bi-layers me-2"></i>
                        {{ __('Duplicates Ignored') }}
                        <span class="badge bg-light text-dark ms-1">{{ $assessment->duplicate_rows }}</span>
                    </div>
                </li>
                @endif

                @if(($assessment->skipped_rows ?? 0) > 0)
                <hr>
                <li>
                    <a class="dropdown-item text-warning fw-semibold"
                       href="{{ route('assessments.download.errors', $assessment->id) }}">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ __('Download Error Report') }}
                        <span class="badge bg-warning text-dark ms-1">{{ $assessment->skipped_rows }}</span>
                    </a>
                </li>
                @endif

            </ul>
        </div>
    </div>
</div>
        <div class="card-body">
            @if ($masterData->isEmpty())
                <p class="text-muted mb-0">{{ __('No master data found for this assessment.') }}</p>
            @else
                <ul class="nav nav-tabs">
    @foreach($sheets as $index => $sheet)
        <li class="nav-item">
            <a class="nav-link {{ $index===0?'active':'' }}"
               data-bs-toggle="tab"
               href="#sheet-{{ $sheet->id }}">
               {{ $sheet->sheet_name }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content mt-3">
@foreach($sheets as $i => $sheet)
    <div class="tab-pane fade {{ $i==0?'show active':'' }}" id="sheet-{{ $sheet->id }}">

        @php
            $rows = $masterData[$sheet->id] ?? [];
        @endphp

        @if(empty($rows))
            <p>{{ __('No data found.') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                    <tr>
                        @foreach($sheet->keys as $key)
                            <th>{{ $key->short_code }}</th>
                        @endforeach
                        <th>{{ __('Action') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($rows as $entryCounter => $rowData)
                        <tr>
                            @foreach($sheet->keys as $key)
                                <td>
                                    {{ $rowData[$key->id] ?? $key->id }}
                                </td>
                            @endforeach
                            <th>
                                <a class="text-danger" href="{{ route('assessments.sheet.archiveSheetEntry', [$assessment->id,$sheet->id,$entryCounter]) }}" onclick="return confirm('{{ __('Are you sure you want to delete this entry?') }}')">
                                    <i class="bi bi-trash me-2"></i>{{ __('Delete') }}
                                </a>
                            </th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
@endforeach
</div>

            @endif
        </div>
    </div>



                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/list.pagination.js/list.pagination.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/crm-companies.init.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clearButton = document.querySelector('#clearDataBtn');

    if (clearButton) {
        clearButton.addEventListener('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: '{{ __('This will permanently delete all master data for this assessment.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, clear it!') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = clearButton.getAttribute('href');
                }
            });
        });
    }
});
</script>

@endsection





