@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.forms')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('css')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            ADMINISTRATION
        @endslot
        @slot('title')
            @lang('translation.forms')
        @endslot
    @endcomponent
    <div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-header">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label>Create @lang('translation.forms')</label>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div>
                        <form action="{{ route('forms.licensee_templates.store') }}" method="POST">
                @csrf
        <div class="row g-3">
            <div class="col-md-6">
                    <label class="form-label">@lang('translation.licensee') <span class="text-danger">*</span></label>
                    <select name="licensee_id" class="form-select" required>
                        <option value="">Select Licensee</option>
                        @foreach($licensees as $id => $name)
                            <option value="{{ $id }}" {{ old('licensee_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">@lang('translation.subfolder') <span class="text-danger">*</span></label>
                    <select name="subfolder_id" class="form-select" required>
                        <option value="">Select Subfolder</option>
                        @foreach($subfolders as $id => $name)
                            <option value="{{ $id }}" {{ old('subfolder_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">@lang('translation.version') <span class="text-danger">*</span></label>
                    <input type="text" name="version" value="{{ old('version') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('translation.department') <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" {{ old('department_id') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('translation.sheetname') <span class="text-danger">*</span></label>
                    <input type="text" name="sheet_name" value="{{ old('sheet_name') }}" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">@lang('translation.status') <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
        </div>
        <br>
        <div class="d-flex justify-content-between">
                    <a href="{{ route('forms.licensee_templates') }}" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Next → Add Keys</button>
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
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection

