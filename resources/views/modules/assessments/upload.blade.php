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
            {{ __('Upload File') }}
        @endslot
@endcomponent
<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <h4 class="mb-sm-1 font-size-18">@lang('translation.assessments')</h4>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
            <div class="card" id="companyList">
                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">{{ __('Upload File') }}</h4>
                                    
                                </div>
                <div class="card-body">
                    <div class="live-preview">

                         <form action="{{ route('assessments.upload', $assessment->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                             <div class="mb-3">
                                <label>{{ __('Choose Excel File (.xlsx / .csv / .txt)') }}</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.csv,.txt" required>
                            </div>
                             <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
                            <input type="hidden" name="licensee_id" value="{{ $assessment->licensee_id }}">
                            <input type="hidden" name="licensee_template_id" value="{{ $assessment->licensee_template_id }}">
                            <input type="hidden" name="assessment_date" value="{{ $assessment->assessment_date }}">
                            <input type="hidden" name="status" value="{{ $assessment->status }}">

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Upload & Validate') }}</button>
                                <a href="{{ route('assessments.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    const fileInput = form.querySelector('input[type="file"]');

    form.addEventListener('submit', function(e) {
        const file = fileInput.files[0];
        if (!file) return;

        const fileName = file.name.toLowerCase();
        const fileSize = file.size / 1024 / 1024; // MB
        let limit = 0;
        let typeLabel = '';

        if (fileName.endsWith('.xlsx')) {
            limit = 20;
            typeLabel = 'Excel';
        } else if (fileName.endsWith('.csv') || fileName.endsWith('.txt')) {
            limit = 2048; // 2GB
            typeLabel = 'CSV/Text';
        } else {
            // Reject other extensions (like .xlsb, .xls, etc.)
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Unsupported File Format',
                text: 'Only .xlsx, .csv, and .txt files are supported. Please convert your file to a supported format.',
                confirmButtonClass: 'btn btn-primary w-xs mt-2',
                buttonsStyling: false
            });
            return false;
        }

        if (limit > 0 && fileSize > limit) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: `${typeLabel} files must be ${limit >= 1024 ? (limit/1024) + 'GB' : limit + 'MB'} or smaller. Your file is ${fileSize.toFixed(1)}MB.`,
                confirmButtonClass: 'btn btn-primary w-xs mt-2',
                buttonsStyling: false,
                footer: '<a href="">Why do I have this issue?</a>'
            });
            return false;
        }
    });
});
</script>
@endsection



















