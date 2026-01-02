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
            SURVEYS
        @endslot
        @slot('title')
            @lang('translation.assessments')
        @endslot
@endcomponent

<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-body">
                    <a href="{{ route('assessments.index') }}" class="btn btn-secondary btn-sm">← Back to List</a><br><br>
                    <!-- Section 1: Assessment Basic Details -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light fw-bold">Basic Information</div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4"><strong>Licensee:</strong></div>
                <div class="col-md-8">{{ $assessment->licensee->name_en ?? '—' }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>Template:</strong></div>
                <div class="col-md-8">
                    {{ $assessment->template->licensee->name_en ?? '' }} -
                    {{ $assessment->template->subfolder->name_en ?? '' }} (v{{ $assessment->template->version }})
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>Assessment Date:</strong></div>
                <div class="col-md-8">{{ \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4"><strong>Status:</strong></div>
                <div class="col-md-8">
                    <span class="badge bg-{{ $assessment->status === 'completed' ? 'success' : ($assessment->status === 'in_progress' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($assessment->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

   <!-- SECTION 2: Master Data (Grouped by Entry Counter) -->
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <span class="fw-bold">Assessment Master Data</span>

    <div class="btn-group">
        
        <!-- Optional Dropdown for More Options -->
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('assessments.upload.form', $assessment->id) }}">
                        <i class="bi bi-download me-2 text-info"></i>Excel Upload
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('assessments.form', $assessment->id) }}">
                        <i class="bi bi-download me-2 text-info"></i>Manual Entry
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-danger" id="clearDataBtn" href="{{ route('assessments.clearData', $assessment->id) }}">
                    <i class="bi bi-trash me-2"></i>Clear All Data
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
        <div class="card-body">
            @if ($masterData->isEmpty())
                <p class="text-muted mb-0">No master data found for this assessment.</p>
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
            <p>No data found.</p>
        @else
            <table class="table table-bordered table-sm">
                <thead>
                <tr>
                    @foreach($sheet->keys as $key)
                        <th>{{ $key->short_code }}</th>
                    @endforeach
                    <th>Action</th>
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
                        <th><a class="dropdown-item text-danger" id="clearDataBtn" href="{{ route('assessments.sheet.archiveSheetEntry', [$assessment->id,$sheet->id,$entryCounter]) }}">
                    <i class="bi bi-trash me-2"></i>Delete
                    </a></th>
                    </tr>
                @endforeach
                </tbody>
            </table>
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
                title: 'Are you sure?',
                text: 'This will permanently delete all master data for this assessment.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = clearButton.getAttribute('href');
                }
            });
        });
    }

    const deleteButton = document.querySelector('#clearDataBtn');

    if (clearButton) {
        clearButton.addEventListener('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete all master data for this assessment.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it!',
                cancelButtonText: 'Cancel',
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





