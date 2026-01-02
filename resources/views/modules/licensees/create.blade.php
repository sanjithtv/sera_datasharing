@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.licensees')
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
            @lang('translation.licensees')
        @endslot
    @endcomponent
    <div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-header">
                    
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
                        <form action="{{ route('licensees.store') }}" method="POST" class="card p-4 shadow-sm">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">@lang('translation.code')</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="name_en" class="form-label">@lang('translation.name') (EN)</label>
                <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="name_ar" class="form-label">@lang('translation.name') (AR)</label>
                <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="name_ar" class="form-label">@lang('translation.status')</label>
                <select name="status" class="form-control">
                    <option value="active" >Active</option>
                    <option value="inactive">InActive</option>
                </select>
            </div>
        </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('licensees.index') }}" class="btn btn-light me-2">@lang('translation.cancel')</a>
                <button type="submit" class="btn btn-success">@lang('translation.save')</button>
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

