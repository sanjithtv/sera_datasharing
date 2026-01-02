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
                        
                    </div>
                </div>
                <div class="card-body">
                    <div>
                           <form method="POST" action="{{ route('security.profile_users.update', $profileUser->id) }}">
        @csrf
        @method('PUT')

        @include('modules.profile_users.form', ['action' => 'edit'])
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



