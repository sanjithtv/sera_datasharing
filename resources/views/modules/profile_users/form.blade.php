@php
App::setLocale(session('lang'));
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">@lang('translation.fullname') (@lang('translation.en'))</label>
        <input type="text" name="fullname_en" class="form-control" 
               value="{{ old('fullname_en', $profileUser->fullname_en ?? '') }}" required>
        @error('fullname_en') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.fullname') (@lang('translation.ar'))</label>
        <input type="text" name="fullname_ar" class="form-control" 
               value="{{ old('fullname_ar', $profileUser->fullname_ar ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.email')</label>
        <input type="email" name="email" class="form-control" 
               value="{{ old('email', $profileUser->email ?? '') }}" required readonly>
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.phone')</label>
        <input type="text" name="phone" class="form-control" 
               value="{{ old('phone', $profileUser->phone ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.designation')</label>
        <input type="text" name="designation" class="form-control" 
               value="{{ old('designation', $profileUser->designation ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.status')</label>
        <select name="status" class="form-select" required>
            <option value="active" {{ old('status', $profileUser->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $profileUser->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="archived" {{ old('status', $profileUser->status ?? '') == 'archived' ? 'selected' : '' }}>Archived</option>
        </select>
    </div>

   @if($action == 'create')
    <div class="col-md-6">
        <label class="form-label">@lang('translation.password')</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.confirm_password')</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    @else
    <div class="col-md-6">
        <label class="form-label">@lang('translation.password') (leave blank to keep current)</label>
        <input type="password" name="password" class="form-control">
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('translation.confirm_password')</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">@lang('translation.role')</label>
        <select name="role_id" class="form-select" required>
            @foreach($roles as $id => $roleName)
                <option value="{{ $id }}" 
                    {{ old('role_id', isset($user) && $user->roles->first()?->id == $id ? 'selected' : '') }}>
                    {{ ucfirst($roleName) }}
                </option>
            @endforeach
        </select>
        @error('role_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-3">
        <button type="submit" class="btn btn-success">@lang('translation.save')</button>
        <a href="{{ route('security.profile_users.index') }}" class="btn btn-secondary">@lang('translation.cancel')</a>
    </div>
</div>
