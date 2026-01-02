@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.departments')
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
            @lang('translation.departments')
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
                        <form action="{{ route('departments.update', $department->id) }}" method="POST" class="card p-4 shadow-sm">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">@lang('translation.code')</label>
                <input type="text" name="code" id="code" class="form-control" value="{{ old('code', $department->code) }}" required>
            </div>
            <div class="col-md-6">
                <label for="name_en" class="form-label">@lang('translation.name') (EN)</label>
                <input type="text" name="name_en" id="name_en" class="form-control" value="{{ old('name_en', $department->name_en) }}" required>
            </div>
            <div class="col-md-6">
                <label for="name_ar" class="form-label">@lang('translation.name') (AR)</label>
                <input type="text" name="name_ar" id="name_ar" class="form-control" value="{{ old('name_ar', $department->name_ar) }}" required>
            </div>
            <div class="col-md-6">
                <label for="name_ar" class="form-label">@lang('translation.status')</label>
                <select name="status" class="form-control">
                    <option value="active" {{$department->status=="active"?'selected':''}}>Active</option>
                    <option value="inactive" {{$department->status=="inaactive"?'selected':''}}>InActive</option>
                </select>
            </div>
            <div class="d-flex justify-content-end">
                <a href="{{ route('departments.index') }}" class="btn btn-light me-2">@lang('translation.cancel')</a>
                <button type="submit" class="btn btn-success">@lang('translation.save')</button>
            </div>
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

