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
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @elseif(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <div class="card-header align-items-center d-flex">
                                    <h4 class="mb-3">Manual Data Entry — Assessment #{{ $assessment->id }}</h4>
                                    
                </div>
                <div class="card-body">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        @foreach($template->sheets as $sheetIndex => $sheet)
                            <li class="nav-item">
                                <button class="nav-link {{ $sheetIndex == 0 ? 'active' : '' }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#sheet_{{ $sheet->id }}">
                                    {{ $sheet->sheet_name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>


                    <!-- SHEET TAB CONTENT -->
                    
                        <div class="tab-content">
                        @foreach($template->sheets as $sheetIndex => $sheet)
                            <div class="tab-pane fade {{ $sheetIndex == 0 ? 'show active' : '' }}" id="sheet_{{ $sheet->id }}">
                                <form method="POST" action="{{ route('assessments.manual.store',$assessment->id) }}" na>
                                @csrf
                                <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                                <input type="hidden" name="sheet_id" value="{{ $sheet->id }}">
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header fw-bold d-flex justify-content-between">
                                        {{ $sheet->name }}
                                        
                                    </div>
                                <div class="card-body table-responsive">
                                    <table class="table table-bordered" id="table_{{ $sheet->id }}">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                @foreach($sheet->keys as $key)
                                                    <th>{{ $key->desc_en }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                    <!-- DEFAULT ROW -->
                                            <tr>
                                                <td>1</td>
                                                @foreach($sheet->keys as $key)
                                                <td>
                                                @switch($key->type)

                                                {{-- ✅ NUMBER --}}
                                                @case('number')
                                                    <input type="number"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ TEXT --}}
                                                @case('text')
                                                    <input type="text"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ SELECT --}}
                                                @case('select')
                                                    <select name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                            class="form-control"
                                                            @if($key->mandatory) required @endif>

                                                        <option value="">-- Select --</option>

                                                        @foreach(explode(',', $key->options ?? '') as $option)
                                                            <option value="{{ trim($option) }}">
                                                                {{ trim($option) }}
                                                            </option>
                                                        @endforeach

                                                    </select>
                                                    @break

                                                {{-- ✅ NUMBER PERCENTAGE --}}
                                                @case('number_percentage')
                                                    <input type="text"
                                                           placeholder="%"
                                                           pattern="^\d+(\.\d+)?%?$"
                                                           title="Enter valid percentage"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ DATE --}}
                                                @case('date')
                                                    <input type="date"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ DATETIME --}}
                                                @case('datetime')
                                                    <input type="datetime-local"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ TIME --}}
                                                @case('time')
                                                    <input type="time"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>
                                                    @break

                                                {{-- ✅ FALLBACK --}}
                                                @default
                                                    <input type="text"
                                                           name="sheets[{{ $sheet->id }}][1][{{ $key->id }}]"
                                                           class="form-control"
                                                           @if($key->mandatory) required @endif>

                                            @endswitch
                                            </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success save-sheet-btn">
                            Save Manual Assessment Data
                        </button>
                    </div>

                    </form>
                    </div>
                    @endforeach
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
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.save-sheet-btn').forEach(btn => {
        btn.addEventListener('click', function () {

            const form = this.closest('form');
            // ✅ Remove required from all inputs first
            document.querySelectorAll('[data-mandatory="1"]').forEach(el => {
                el.removeAttribute('required');
            });

            // ✅ Apply required ONLY inside this form
            form.querySelectorAll('[data-mandatory="1"]').forEach(el => {
                el.setAttribute('required', 'required');
            });

            form.submit();
        });
    });

});
</script>


@endsection

