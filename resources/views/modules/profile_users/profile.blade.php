@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title', 'My Profile')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') USER @endslot
    @slot('title') My Profile @endslot
@endcomponent

<div class="row">
    <div class="col-xxl-4 col-md-5">
        <div class="card">
            <div class="card-body text-center">

                <img src="{{ asset('build/images/users/user-dummy-img.jpg') }}"
                     class="rounded-circle avatar-xl mb-3" alt="">

                <h4 class="mb-1">{{ $profileUser->fullname_en }}</h4>

                <p class="text-muted mb-3">{{ $profileUser->designation ?? '—' }}</p>

                <a href="{{ route('profileuser.edit') }}" class="btn btn-primary btn-sm">
                    <i class="ri-edit-box-line me-1"></i> Edit Profile
                </a>

                <hr>

                <h5 class="text-start mt-4 mb-3">Contact Information</h5>

                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th>Email:</th>
                            <td>{{ $profileUser->email }}</td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>{{ $profileUser->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-success">{{ ucfirst($profileUser->status) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <hr>

                <h5 class="text-start mt-4 mb-3">User Role</h5>
                <span class="badge bg-secondary">
                    {{ $user->getRoleNames()->first() }}
                </span>

            </div>
        </div>
    </div>

    <div class="col-xxl-8 col-md-7">
        <div class="card">
            <div class="card-header bg-light fw-bold">About Me</div>
            <div class="card-body">
                <p>{{ $profileUser->fullname_en }} is currently working as <strong>{{ $profileUser->designation ?? '—' }}</strong>.</p>
                <p>This profile belongs to the user registered under the system.</p>
            </div>
        </div>
    </div>
</div>

@endsection
