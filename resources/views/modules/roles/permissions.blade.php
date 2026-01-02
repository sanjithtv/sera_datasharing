@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.profile_users')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('css')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            SECURITY
        @endslot
        @slot('title')
            @lang('translation.profile_users')
        @endslot
    @endcomponent
    <div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-header">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search for @lang('translation.licensee')...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-auto ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-info add-btn" data-bs-toggle="modal" data-bs-target="#showModal"><i
                                    class="ri-add-fill me-1 align-bottom"></i> @lang('translation.new_user')</button>
                                <button type="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false"
                                    class="btn btn-soft-info"><i class="ri-more-2-fill"></i></button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1">
                                    <li><a class="dropdown-item" href="#">Export as Excel</a></li>
                                </ul>  
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <form method="POST" action="{{ route('security.roles.permissions.update', $role->id) }}">
        @csrf

        <div class="row">
            @foreach($groupedPermissions as $module => $permissions)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light fw-bold">
                    {{ $module }} Module
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($permissions as $perm)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        id="perm_{{ $perm->id }}"
                                        name="permissions[]"
                                        value="{{ $perm->name }}"
                                        {{ in_array($perm->name, $rolePermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm_{{ $perm->id }}">
                                        {{ ucfirst(str_replace(['.', '_'], ' ', $perm->name)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Update Permissions
            </button>
            <a href="{{ route('security.roles.index') }}" class="btn btn-secondary">Back</a>
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
