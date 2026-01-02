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
                
                <div class="card-body">
                    <div>

    {{-- Template Update Form --}}
    <form method="POST" action="{{ route('forms.licensee_templates.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="licenseeTemplate_id" value="{{$licenseeTemplate->id}}">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label>@lang('translation.licensee')</label>
                <select name="licensee_id" class="form-select">
                    @foreach($licensees as $id => $name)
                        <option value="{{ $id }}" {{ $licenseeTemplate->licensee_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>@lang('translation.subfolder')</label>
                <select name="subfolder_id" class="form-select">
                    @foreach($subfolders as $id => $name)
                        <option value="{{ $id }}" {{ $licenseeTemplate->subfolder_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label>@lang('translation.version')</label>
                <input type="text" name="version" value="{{ $licenseeTemplate->version }}" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label>@lang('translation.department')</label>
                <select name="department_id" class="form-select">
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" {{ $licenseeTemplate->department_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>@lang('translation.sheetname')</label>
                <input type="text" name="sheet_name" value="{{ $licenseeTemplate->sheet_name }}" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label>@lang('translation.status')</label>
                <select name="status" class="form-select">
                    <option value="active" {{ $licenseeTemplate->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $licenseeTemplate->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <button class="btn btn-success mb-3">@lang('translation.update_template')</button>
    </form>

    <hr>

    {{-- Add New Key --}}
    <h4 class="mt-4">@lang('translation.template_keys')</h4>
    <form method="POST" action="{{ route('forms.licensee_templates.keys.store', $licenseeTemplate->id) }}">
        @csrf
        <input type="hidden" name="licensee_template_id" value="{{$licenseeTemplate->id}}">
        <input type="hidden" name="licensee_id" value="{{$licenseeTemplate->licensee_id}}">
        <div class="row align-items-end">
            <div class="col-md-2">
                <label>@lang('translation.sheetname')</label>
                <select name="sheet_id" class="form-select">
                    @foreach($sheets as $sheetId => $keys)
                    <option value="{{$sheetId}}">{{$keys}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>@lang('translation.code')</label>
                <input name="short_code" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label>@lang('translation.description') (EN)</label>
                <input name="desc_en" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label>@lang('translation.description') (AR)</label>
                <input name="desc_ar" class="form-control">
            </div>
            <div class="col-md-2">
                <label>@lang('translation.mandatory')</label>
                <select name="mandatory" class="form-select">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>@lang('translation.type')</label>
                <select name="type" class="form-select" required>
                    <option value="text">Text</option>
                    <option value="number">Integer</option>
                    <option value="number_percentage">Number Percentage</option>
                    <option value="date">Date</option>
                    <option value="datetime">DateTime</option>
                    <option value="time">Time</option>
                </select>
            </div>
            <input type="hidden" name="licensee_id" value="{{ $licenseeTemplate->licensee_id }}">
        </div>
        <button class="btn btn-primary mt-3">@lang('translation.add_key')</button>
    </form>

    {{-- Table of Keys --}}
    <table class="table table-bordered mt-4" id="keysTable">
        <thead class="table-light">
            <tr>
                <th>@lang('translation.code')</th>
                <th>EN</th>
                <th>AR</th>
                <th>@lang('translation.mandatory')</th>
                <th>@lang('translation.type')</th>
                <th>@lang('translation.action')</th>
            </tr>
        </thead>
        @php
        $groupedKeys = $templateKeys->groupBy('sheet_id');
        @endphp
        <tbody>
            @foreach($groupedKeys as $sheetId => $keys)
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light fw-bold">
                Sheet: {{ $keys->first()->sheet->sheet_name ?? 'Unknown Sheet' }}
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Short Code</th>
                            <th>Description (EN)</th>
                            <th>Description (AR)</th>
                            <th>Mandatory</th>
                            <th>Type</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($keys as $key)
                            <tr>
                                <form method="POST" action="{{ route('forms.licensee_templates.keys.update', $key->id) }}">
                                    @csrf
                                    @method('PUT')

                                    <td>
                                        <input type="text" name="short_code" value="{{ $key->short_code }}" class="form-control" required>
                                    </td>

                                    <td>
                                        <input type="text" name="desc_en" value="{{ $key->desc_en }}" class="form-control" required>
                                    </td>

                                    <td>
                                        <input type="text" name="desc_ar" value="{{ $key->desc_ar }}" class="form-control">
                                    </td>

                                    <td>
                                        <select name="mandatory" class="form-select">
                                            <option value="1" {{ $key->mandatory ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$key->mandatory ? 'selected' : '' }}>No</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select name="type" class="form-select">
                                            <option value="text" {{ $key->type == 'text' ? 'selected' : '' }}>Text</option>
                                            <option value="number" {{ $key->type == 'number' ? 'selected' : '' }}>Number</option>
                                            <option value="select" {{ $key->type == 'select' ? 'selected' : '' }}>Select</option>
                                            <option value="number_percentage" {{ $key->type == 'number_percentage' ? 'selected' : '' }}>Number Percentage</option>
                                            <option value="date" {{ $key->type == 'date' ? 'selected' : '' }}>Date</option>
                                            <option value="datetime" {{ $key->type == 'datetime' ? 'selected' : '' }}>Datetime</option>
                                            <option value="time" {{ $key->type == 'time' ? 'selected' : '' }}>Time</option>
                                        </select>
                                    </td>

                                    <td>
                                        <button class="btn btn-sm btn-success">Update</button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
        </tbody>
    </table>
</div>


{{-- Edit Modal --}}
<div class="modal fade" id="editKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editKeyForm" name="editKeyForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editKeyId" name="id">
                    <div class="mb-3">
                        <label>EN Description</label>
                        <input type="text" id="editDescEn" name="desc_en" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>AR Description</label>
                        <input type="text" id="editDescAr" name="desc_ar" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Mandatory</label>
                        <select id="editMandatory" name="mandatory" class="form-select">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select id="editType" name="type" class="form-select" required>
                            <option value="text">Text</option>
                            <option value="number">Integer</option>
                            <option value="number_percentage">Number Percentage</option>
                            <option value="date">Date</option>
                            <option value="datetime">DateTime</option>
                            <option value="time">Time</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
            <!--end card-->
        </div>
        <!--end col-->
    </div>
    <!--end row-->

<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = new bootstrap.Modal(document.getElementById('editKeyModal'));

    // Open modal with data
    document.querySelectorAll('.edit-key-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editKeyId').value = this.dataset.id;
            document.getElementById('editDescEn').value = this.dataset.en;
            document.getElementById('editDescAr').value = this.dataset.ar;
            document.getElementById('editMandatory').value = this.dataset.mandatory;
            document.getElementById('editType').value = this.dataset.type;
            modal.show();
        });
    });

    // Submit edit form via AJAX
    document.getElementById('editKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const id = document.getElementById('editKeyId').value;
    const formData = new FormData(this);
    for (const [key, value] of formData.entries()) {
        console.log(key, value);
    }
    const data = Object.fromEntries(new FormData(this).entries());
    fetch(`/forms/licensee_templates/keys/${id}`, {
    method: 'PUT',
    headers: {
    'X-CSRF-TOKEN': '{{ csrf_token() }}',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(data)
})
    .then(async res => {
        if (!res.ok) {
            const text = await res.text();
            console.error('Response not OK:', text);
            throw new Error('Request failed: ' + res.status);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Update failed.');
        }
    })
    .catch(err => console.error('Error:', err));
});


    // Delete key via AJAX
    document.querySelectorAll('.delete-key-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this key?')) return;
            const id = this.dataset.id;
            fetch(`/forms/licensee_templates/keys/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`key-row-${id}`).remove();
                }
            });
        });
    });
});
</script>

@endsection
