@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')

@section('title', __('Excel Import Preview'))

@section('content')

@component('components.breadcrumb')
    @slot('li_1') {{ __('Assessments') }} @endslot
    @slot('title') {{ __('Excel Upload Preview') }} @endslot
@endcomponent

<div class="card shadow-sm">

    {{-- HEADER --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Excel Import Preview') }}</h5>

        @if ($canProceed)
            <span class="badge bg-success">{{ __('Ready to Import') }}</span>
        @else
            <span class="badge bg-danger">{{ __('Validation Errors Found') }}</span>
        @endif
    </div>

    <div class="card-body">

        {{-- ASSESSMENT INFO --}}
        <div class="alert alert-light border mb-4">
            <div><strong>{{ __('Assessment ID:') }}</strong> {{ $assessment->id }}</div>
            <div><strong>{{ __('Licensee:') }}</strong> {{ $assessment->licensee->name_ar ?? $assessment->licensee->name_en }}</div>
            <div><strong>{{ __('Template Version:') }}</strong> v{{ $assessment->template->version }}</div>
        </div>

        {{-- SHEET TABS --}}
        <ul class="nav nav-tabs mb-3" role="tablist">
            @php $tabIndex = 0; @endphp

            @foreach ($errorsPerSheet as $sheetId => $sheetErrors)
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $tabIndex === 0 ? 'active' : '' }}"
                        data-bs-toggle="tab"
                        data-bs-target="#sheet-tab-{{ $tabIndex }}"
                        type="button"
                        role="tab">

                        {{ $sheetId }}

                        @php
                            $errCount = $totalErrorsPerSheet[$namePerSheet[$sheetId]] ?? 0;
                        @endphp

                        @if ($errCount > 0)
                            <span class="badge bg-danger">{{ $errCount }}</span>
                        @endif
                    </button>
                </li>
                @php $tabIndex++; @endphp
            @endforeach
        </ul>

        {{-- TAB CONTENT --}}
        <div class="tab-content">
            @php $tabIndex = 0; @endphp

            @php $tabIndex = 0; @endphp
            @foreach ($errorsPerSheet as $sheetName => $sheetErrors)

                @php
                    //$sheetName = $namePerSheet[$sheetId] ?? 'Sheet';

                    $headerError = collect($sheetErrors)
                        ->where('type', 'header_validation')
                        ->first();
                       
                    $sheetRows = $previewRows->where('sheet_id', $namePerSheet[$sheetName]);
                @endphp

                <div class="tab-pane fade {{ $tabIndex === 0 ? 'show active' : '' }}"
                     id="sheet-tab-{{ $tabIndex }}"
                     role="tabpanel">

                    <h6 class="fw-bold mb-3">{{ $sheetName }}</h6>

                    {{-- HEADER VALIDATION ERRORS --}}
                    @if ($headerError)
                        <div class="alert alert-danger border">
                            <h6 class="fw-bold mb-2">
                                <i class="ri-error-warning-line"></i>
                                {{ __('Column Validation Error') }}
                            </h6>

                            <p class="mb-2">
                                {{ __('The uploaded Excel columns do not match the configured template for this sheet.') }}
                            </p>

                            @if (!empty($headerError['errors']['missing_columns']))
                                <div class="mb-2">
                                    <strong>{{ __('Missing Columns:') }}</strong>
                                    <ul class="mb-0">
                                        @foreach ($headerError['errors']['missing_columns'] as $col)
                                            <li>{{ $col }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($headerError['errors']['extra_columns']))
                                <div>
                                    <strong>{{ __('Extra Columns Found:') }}</strong>
                                    <ul class="mb-0">
                                        @foreach ($headerError['errors']['extra_columns'] as $col)
                                            <li>{{ $col }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <p class="text-muted">
                            {{ __('Preview is disabled for this sheet. Please fix the Excel headers and re-upload.') }}
                        </p>

                    {{-- MISSING SHEET ERRORS --}}
                    @elseif ($missingSheetError = collect($sheetErrors)->where('type', 'missing_sheet')->first())
                        <div class="alert alert-danger border">
                            <h6 class="fw-bold mb-2">
                                <i class="ri-error-warning-line"></i>
                                {{ __('Missing Sheet Error') }}
                            </h6>
                            <p class="mb-0">
                                {{ $missingSheetError['message'] }}
                            </p>
                        </div>
                        <p class="text-muted">
                            {{ __('Preview is disabled. Please ensure all required sheets are present and re-upload.') }}
                        </p>

                    {{-- EMPTY SHEET ERRORS --}}
                    @elseif ($emptySheetError = collect($sheetErrors)->where('type', 'empty_sheet')->first())
                        <div class="alert alert-warning border">
                            <h6 class="fw-bold mb-2">
                                <i class="ri-file-warning-line"></i>
                                {{ __('Empty Sheet Error') }}
                            </h6>
                            <p class="mb-2">
                                {{ $emptySheetError['message'] }}
                            </p>
                            @if (!empty($emptySheetError['missing_columns']))
                                <div>
                                    <strong>{{ __('Required Expected Columns:') }}</strong>
                                    <ul class="mb-0">
                                        @foreach ($emptySheetError['missing_columns'] as $col)
                                            <li>{{ $col }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        <p class="text-muted">
                            {{ __('This sheet does not contain any recognizable columns or data.') }}
                        </p>

                    {{-- ROW PREVIEW --}}
                    @elseif ($sheetRows->isEmpty())
                        <p class="text-muted">{{ __('No preview data available for this sheet.') }}</p>

                    @else

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('#') }}</th>

                                        @php
                                            $rawHeaders = $sheetRows->first()->headers;
                                            $headers = is_string($rawHeaders) ? json_decode($rawHeaders, true) : $rawHeaders;
                                        @endphp

                                        @foreach ($headers as $humanName => $shortCode)
                                            <th>{{ $humanName }}</th>
                                        @endforeach

                                        <th>{{ __('Errors') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($sheetRows as $row)
                                        @php
                                            $rowValues = is_string($row->row_data) ? json_decode($row->row_data, true) : $row->row_data;
                                            $rowErrors = is_string($row->validation_errors) ? json_decode($row->validation_errors, true) : $row->validation_errors;
                                        @endphp

                                        <tr class="{{ !empty($rowErrors) ? 'table-danger' : '' }}">
                                            <td>{{ $row->row_index }}</td>

                                            @foreach ($headers as $humanName => $shortCode)
                                                @php
                                                    $cellVal = $rowValues[$shortCode] ?? null;
                                                    $sentinels = [
                                                        '__INVALID_DATE__'     => ['text' => __('Invalid Date'),     'icon' => '⚠'],
                                                        '__INVALID_NUMBER__'   => ['text' => __('Invalid Number'),   'icon' => '⚠'],
                                                        '__INVALID_TIME__'     => ['text' => __('Invalid Time'),     'icon' => '⚠'],
                                                    ];
                                                    $isSentinel = is_string($cellVal) && isset($sentinels[$cellVal]);
                                                @endphp
                                                <td>
                                                    @if ($isSentinel)
                                                        <span class="badge bg-warning text-dark fw-semibold">
                                                            {{ $sentinels[$cellVal]['icon'] }} {{ $sentinels[$cellVal]['text'] }}
                                                        </span>
                                                    @elseif ($cellVal === null || $cellVal === '')
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        {{ $cellVal }}
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td>
                                                @if (empty($rowErrors))
                                                    <span class="badge bg-success">OK</span>
                                                @else
                                                    @foreach ($rowErrors as $col => $errList)
                                                        <div class="text-danger small">
                                                            <strong>{{ $col }}:</strong>
                                                            {{ implode(', ', $errList) }}
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>

                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif

                </div>

                @php $tabIndex++; @endphp
            @endforeach
        </div>

        {{-- ACTION BUTTONS --}}
        @php
            $totalErrors = $totalErrorCount;
        @endphp
        <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('assessments.index') }}" class="btn btn-secondary">
                {{ __('Cancel') }}
            </a>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                @if ($canProceed)
                    {{-- All rows are clean: standard full import --}}
                    <form action="{{ route('assessments.importData',$assessment->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-check-double-line me-1"></i>
                            {{ __('Proceed & Import All Data') }}
                        </button>
                    </form>
                @else
                    {{-- Option: skip bad rows, import the rest now --}}
                    <button type="button"
                            class="btn btn-warning fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#importValidModal">
                        <i class="ri-upload-cloud-line me-1"></i>
                        {{ __('Import Valid Rows Only') }}
                        <span class="badge bg-dark ms-1">{{ $totalValidCount }}+</span>
                    </button>
                @endif
            </div>
        </div>

    </div>
</div>


{{-- ============================================================
     Confirmation modal for "Import Valid Rows Only"
     ============================================================ --}}
<div class="modal fade" id="importValidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="ri-upload-cloud-line me-2"></i>{{ __('Import Valid Rows Only?') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    <strong>{{ $totalErrors }}</strong> {{ __('row(s) with validation errors will be') }}
                    <span class="text-danger fw-semibold">{{ __('skipped') }}</span>.
                    {{ __('All other valid rows will be imported normally.') }}
                </p>
                <p class="text-muted small mb-0">
                    {{ __('You can download an') }} <strong>{{ __('Error Report') }}</strong> {{ __('before or after importing to review the skipped rows.') }}
                </p>
            </div>
            <div class="modal-footer">
                <a href="{{ route('assessments.download.step1.errors', $assessment->id) }}"
                   class="btn btn-outline-warning me-auto btn-sm">
                    <i class="ri-download-2-line me-1"></i>{{ __('Download Error Report') }}
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <form id="importValidForm" action="{{ route('assessments.importData', $assessment->id) }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                    <input type="hidden" name="skip_errors" value="1">
                    <button type="button" class="btn btn-warning fw-semibold" onclick="downloadAndImport()">
                        <i class="ri-check-line me-1"></i>{{ __('Yes, Import Valid Rows') }}
                    </button>
                </form>

                <script>
                function downloadAndImport() {
                    // 1. Kick off the error report download in the background
                    var link = document.createElement('a');
                    link.href = '{{ route('assessments.download.step1.errors', $assessment->id) }}';
                    link.download = '';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // 2. Submit the import form after a short delay so the download starts first
                    setTimeout(function() {
                        document.getElementById('importValidForm').submit();
                    }, 500);
                }
                </script>
            </div>
        </div>
    </div>
</div>

@endsection