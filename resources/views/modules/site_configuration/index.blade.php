@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title', 'Site Configuration')

@section('content')
<div class="card">
    <div class="card-header bg-light fw-bold">Site Configuration</div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#main">Main</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#email">Email</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#security">Security</a></li>
            </ul>

            <div class="tab-content">
                <!-- MAIN -->
                <div class="tab-pane fade show active" id="main">
                    <div class="mb-3">
                        <label>Application Title</label>
                        <input type="text" name="app_title" class="form-control" value="{{ $settings['app_title'] }}">
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="app_description" class="form-control">{{ $settings['app_description'] }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>Logo</label><br>
                        @if($settings['app_logo'])
                            <img src="{{ asset('storage/'.$settings['app_logo']) }}" height="80" class="mb-2 d-block">
                        @endif
                        <input type="file" name="app_logo" class="form-control">
                    </div>
                </div>

                <!-- EMAIL -->
                <div class="tab-pane fade" id="email">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>From Name</label>
                            <input type="text" name="mail_from_name" class="form-control" value="{{ $settings['mail_from_name'] }}">
                        </div>
                        <div class="col-md-6 mb-3"><label>From Email</label>
                            <input type="email" name="mail_from_email" class="form-control" value="{{ $settings['mail_from_email'] }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="{{ $settings['smtp_host'] }}">
                        </div>
                        <div class="col-md-3 mb-3"><label>SMTP Port</label>
                            <input type="text" name="smtp_port" class="form-control" value="{{ $settings['smtp_port'] }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-control" value="{{ $settings['smtp_username'] }}">
                        </div>
                        <div class="col-md-6 mb-3"><label>SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-control" value="{{ $settings['smtp_password'] }}">
                        </div>
                    </div>
                </div>

                <!-- SECURITY -->
                <div class="tab-pane fade" id="security">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Password Policy</label>
                            <select name="password_policy" class="form-select">
                                <option value="low" {{ $settings['password_policy']=='low'?'selected':'' }}>Low</option>
                                <option value="medium" {{ $settings['password_policy']=='medium'?'selected':'' }}>Medium</option>
                                <option value="high" {{ $settings['password_policy']=='high'?'selected':'' }}>High</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Password Expiry (days)</label>
                            <input type="number" name="password_expiry_days" class="form-control" value="{{ $settings['password_expiry_days'] }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" class="form-control" value="{{ $settings['max_login_attempts'] }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Lockout Duration (minutes)</label>
                            <input type="number" name="lockout_minutes" class="form-control" value="{{ $settings['lockout_minutes'] }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Enable Two-Factor Authentication</label>
                            <select name="two_factor_auth" class="form-select">
                                <option value="disabled" {{ $settings['two_factor_auth']=='disabled'?'selected':'' }}>Disabled</option>
                                <option value="email" {{ $settings['two_factor_auth']=='email'?'selected':'' }}>Email Verification</option>
                                <option value="app" {{ $settings['two_factor_auth']=='app'?'selected':'' }}>Authenticator App</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" value="{{ $settings['session_timeout'] }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-success mt-3">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
