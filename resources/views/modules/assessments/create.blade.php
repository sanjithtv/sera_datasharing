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
                                    <h4 class="card-title mb-0 flex-grow-1"></h4>
                                    
                                </div>
                <div class="card-body">
                    <div class="live-preview">

                        <form action="{{ route('assessments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row gy-4">
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label>@lang('translation.licensee')</label>
                                    <select name="licensee_id" class="form-select" required>
                                        <option value="">@lang('translation.select')</option>
                                        @foreach($licensees as $licensee)
                                            <option value="{{ $licensee->id }}">{{ $licensee->name_en }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-xxl-6 col-md-6">
                                <label>@lang('translation.template')</label>
                                <select name="licensee_template_id" class="form-select" required>
                                    <option value="">@lang('translation.select')</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label>@lang('translation.assessment_date')</label>
                                <input type="date" name="assessment_date" class="form-control" required>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label>@lang('translation.status')</label>
                                <select name="status" class="form-select">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label>@lang('translation.data_entry_mode')</label>
                                <select name="entry_mode" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="excel">Excel Upload</option>
                                    <option value="manual">Manual Entry</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Continue</button>
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





















