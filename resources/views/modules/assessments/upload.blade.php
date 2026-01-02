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
                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Input Example</h4>
                                    
                                </div>
                <div class="card-body">
                    <div class="live-preview">

                         <form action="{{ route('assessments.upload', $assessment->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label>Choose Excel File (.xlsx / .csv)</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                             <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                            <input type="hidden" name="licensee_id" value="{{ $assessment->licensee_id }}">
                            <input type="hidden" name="licensee_template_id" value="{{ $assessment->licensee_template_id }}">
                            <input type="hidden" name="assessment_date" value="{{ $assessment->assessment_date }}">
                            <input type="hidden" name="status" value="{{ $assessment->status }}">

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Upload & Validate</button>
                                <a href="{{ route('assessments.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                        
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
@endsection



















