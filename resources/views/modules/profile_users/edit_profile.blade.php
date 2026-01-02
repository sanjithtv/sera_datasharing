@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title', 'Edit Profile')

@section('content')
@component('components.breadcrumb')
    @slot('li_1') USER @endslot
    @slot('title') Edit Profile @endslot
@endcomponent

<div class="card">
    <div class="card-body">

        <ul class="nav nav-tabs mb-3" id="profileTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab_profile">
                    <i class="ri-user-3-line me-1"></i> Profile Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_password">
                    <i class="ri-lock-password-line me-1"></i> Change Password
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ✅ TAB 1: PROFILE OVERVIEW -->
            <div class="tab-pane fade show active" id="tab_profile">
                <form action="{{ route('profileuser.update') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name (EN)</label>
                            <input type="text" name="fullname_en" value="{{ $profileUser->fullname_en }}"
                                   class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name (AR)</label>
                            <input type="text" name="fullname_ar" value="{{ $profileUser->fullname_ar }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ $profileUser->phone }}"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" value="{{ $profileUser->designation }}"
                                   class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-3-line me-1"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- ✅ TAB 2: CHANGE PASSWORD -->
            <div class="tab-pane fade" id="tab_password">
                <form action="{{ route('profileuser.changePassword') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="ri-refresh-line me-1"></i> Update Password
                    </button>

                </form>
            </div>

        </div>

    </div>
</div>

@endsection
