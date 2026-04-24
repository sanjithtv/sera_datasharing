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
            @lang('translation.forms')
        @endslot
        @slot('title')
            @lang('translation.edit')
        @endslot
@endcomponent
 <div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <h4 class="mb-sm-1 font-size-18">@lang('translation.forms')</h4>
            <div class="card" id="companyList">
                
                <div class="card-body">
                    <div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

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
                <label>@lang('translation.classification')</label>
                <select name="classification_id" class="form-select">
                    @foreach($classifications as $id => $name)
                        <option value="{{ $id }}" {{ $licenseeTemplate->classification_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-3">
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

    <div class="row">
        <div class="col-md-12">
            {{-- Add New Sheet --}}
            <h4 class="mt-4">@lang('translation.add_new_sheet')</h4>
            <form method="POST" action="{{ route('forms.licensee_templates.sheets.store', $licenseeTemplate->id) }}">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label>@lang('translation.sheet_name')</label>
                        <input name="sheet_name" class="form-control" placeholder="e.g. Sheet 2" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary mt-3">@lang('translation.add_sheet')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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
                    <option value="3">Auto</option>
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

    <div class="mt-4">
        @php
        $groupedKeys = $templateKeys->groupBy('sheet_id');
        @endphp

        @foreach($sheets as $sheetId => $sheetName)
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold">Sheet: {{ $sheetName }}</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary">{{ $groupedKeys->has($sheetId) ? $groupedKeys[$sheetId]->count() : 0 }} Keys</span>
                    @if(!$groupedKeys->has($sheetId) || $groupedKeys[$sheetId]->count() == 0)
                        <form method="POST" action="{{ route('forms.licensee_templates.sheets.delete', $sheetId) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this empty sheet?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Empty Sheet">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    @endif
                </div>
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
                        @if($groupedKeys->has($sheetId))
                            @foreach($groupedKeys[$sheetId] as $key)
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
                                                <option value="1" {{ $key->mandatory == '1' ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ $key->mandatory == '0' ? 'selected' : '' }}>No</option>
                                                <option value="3" {{ $key->mandatory == '3' ? 'selected' : '' }}>Auto</option>
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
                                            <button type="submit" class="btn btn-sm btn-success">Update</button>
                                            <button type="button" class="btn btn-sm btn-danger delete-key-btn" data-id="{{ $key->id }}">Delete</button>
                                        </td>
                                    </form>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center text-muted">No keys found for this sheet.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
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
