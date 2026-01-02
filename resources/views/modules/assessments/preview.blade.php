@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.import_assessment')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('css')
<style>
    .error-cell {
        background-color: #ffcccc;
        position: relative;
    }
    .error-tooltip {
        display: none;
        position: absolute;
        top: -25px;
        left: 0;
        background: #d9534f;
        color: white;
        font-size: 0.8rem;
        padding: 2px 5px;
        border-radius: 4px;
        white-space: nowrap;
        z-index: 10;
    }
    .error-cell:hover .error-tooltip {
        display: block;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.assessments')
        @endslot
        @slot('title')
            @lang('translation.import_assessment')
        @endslot
@endcomponent


<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Preview Import Data</h4>
                </div>
                <div class="card-body">
                    @if (!empty($validationErrors['headers']))
                    <div class="alert alert-danger">
                        <strong>Header validation issues:</strong><br>
                        @if(!empty($validationErrors['headers']['missing']))
                            Missing: {{ implode(', ', $validationErrors['headers']['missing']) }}<br>
                        @endif
                        @if(!empty($validationErrors['headers']['extra']))
                            Extra: {{ implode(', ', $validationErrors['headers']['extra']) }}
                        @endif
                    </div>
                    @endif
                    <div class="live-preview">
                        <div class="table-responsive table-card mb-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @foreach($headers as $header)
                                            <th>{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataRows as $row)
            @php
                $rowData = $row->row_data;
                $errors = $row->validation_errors;
            @endphp
            <tr class="{{ !empty($errors) ? 'table-danger' : '' }}">
                @foreach($rowData as $col)
                    <td>{{ $col }}</td>
                @endforeach
            </tr>
        @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(empty($validationErrors['headers']) && count($validationErrors) === 0)
                        <form action="{{ route('assessments.commit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="assessment_id" value="{{$assessment->id}}">
                            <input type="submit" class="btn btn-success" name="submit" value="Confirm & Import (Async)">
                        </form>
                        @else
                        <div class="alert alert-warning mt-3">⚠️ Please correct the highlighted cells and re-upload the file.</div>
                        @endif
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
@endsection











