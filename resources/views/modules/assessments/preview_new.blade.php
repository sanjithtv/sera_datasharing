@extends('layouts.master')

@section('title', 'Excel Import Preview')

@section('content')

@component('components.breadcrumb')
    @slot('li_1') Assessments @endslot
    @slot('title') Excel Upload Preview @endslot
@endcomponent

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Excel Import Preview</h5>

        @if ($canProceed)
            <span class="badge bg-success">All Good</span>
        @else
            <span class="badge bg-danger">Validation Errors Found</span>
        @endif
    </div>

    <div class="card-body">

        <!-- Assessment Details -->
        <div class="alert alert-light border mb-4">
            <strong>Assessment:</strong> {{ $assessment->id }} <br>
            <strong>Licensee:</strong> {{ $assessment->licensee->name_en }} <br>
            <strong>Template:</strong> v{{ $assessment->template->version }}
        </div>

        <!-- SHEET TABS -->
        <ul class="nav nav-tabs mb-3" id="sheetTabs" role="tablist">

            @php $tabIndex = 0; @endphp

            @foreach($errorsPerSheet as $sheetName => $rows)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tabIndex === 0 ? 'active' : '' }}"
                            id="tab-{{ $tabIndex }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-{{ $tabIndex }}"
                            type="button"
                            role="tab">
                        {{ $sheetName }}

                        @php
                            $errCount = collect($rows)->filter(fn($x) => !empty($x))->count();
                        @endphp

                        @if ($errCount > 0)
                            <span class="badge bg-danger">{{ $errCount }}</span>
                        @endif
                    </button>
                </li>
                @php $tabIndex++; @endphp
            @endforeach

        </ul>

        <!-- TAB CONTENT -->
        <div class="tab-content">

            @php $tabIndex = 0; @endphp
            @foreach($errorsPerSheet as $sheetName => $sheetErrors)

                @php
                    // Filter preview rows per sheet
                    $sheetRows = $previewRows->where('sheet_id', $namePerSheet[$sheetName]);
                @endphp

                <div class="tab-pane fade {{ $tabIndex === 0 ? 'show active' : '' }}" id="tab-{{ $tabIndex }}" role="tabpanel">

                    <h6 class="fw-bold mb-3">{{ $sheetName }}</h6>

                    @if ($sheetRows->isEmpty())
                        <p class="text-muted">No preview data available for this sheet.</p>
                    @else

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>

                                        @php
                                            $headers = $sheetRows->first()->headers;
                                        @endphp

                                        @foreach ($headers as $head)
                                            <th>{{ $head }}</th>
                                        @endforeach

                                        <th>Errors</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($sheetRows as $row)
                                        @php
                                            $rowValues = $row->row_data;
                                            $rowErrors = $row->validation_errors;
                                        @endphp

                                        <tr class="{{ !empty($rowErrors) ? 'table-danger' : '' }}">
                                            <td>{{ $row->row_index }}</td>

                                            @foreach ($headers as $head)
                                                <td>{{ $rowValues[$head] }}</td>
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

        <!-- ACTION BUTTONS -->
        <div class="mt-4 d-flex justify-content-between">

            <a href="{{ route('assessments.index') }}" class="btn btn-secondary">
                Cancel
            </a>

            @if ($canProceed)
                <form action="{{ route('assessments.importData',$assessment->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                    <button type="submit" class="btn btn-primary">
                        Proceed & Import Data
                    </button>
                </form>
            @else
                <button class="btn btn-danger" disabled>
                    Fix Errors in Excel & Re-upload
                </button>
            @endif

        </div>

    </div>
</div>

@endsection
