@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('css')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.assessments')
        @endslot
        @slot('title')
            @lang('translation.list')
        @endslot
@endcomponent
<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <h4 class="mb-sm-1 font-size-18">@lang('translation.assessments')</h4>
            <div class="card" id="companyList">
                <div class="card-header">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" id="tableSearch" placeholder="@lang('translation.search')...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-auto ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <a class="btn btn-info add-btn" href="{{ route('assessments.create') }}"><i
                                    class="ri-add-fill me-1 align-bottom"></i> @lang('translation.new_assessment')</a>
                                <button type="button" id="dropdownMenuLink1" data-bs-toggle="dropdown" aria-expanded="false"
                                    class="btn btn-soft-info"><i class="ri-more-2-fill"></i></button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink1">
                                    <li><a class="dropdown-item" href="{{ route('assessments.export.excel') }}">{{ __('Export as Excel') }}</a></li>
                                </ul>  
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @elseif(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sort" data-sort="id" scope="col">ID</th>
                                        
                                        <th class="sort" data-sort="licensee" scope="col">@lang('translation.licensee')</th>
                                        <th class="sort" data-sort="subfolder" scope="col">@lang('translation.subfolder')</th>
                                        <th class="sort" data-sort="version" scope="col">@lang('translation.version')</th>
                                        <th>@lang('translation.date')</th>
                                        <th class="sort" data-sort="status" scope="col">@lang('translation.status')</th>
                                        <th>@lang('translation.entries')</th>
                                        <th>@lang('translation.action')</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @forelse ($assessments as $index => $assessment)
                                    <tr>
                                        <td class="id">{{ $assessment->id }}</td>
                                        <td class="licensee">{{ $assessment->licensee->name_en ?? '—' }}</td>
                                        <td class="subfolder">{{ $assessment->licenseeTemplate->subfolder->name_en ?? '—' }}</td>
                                        <td class="version">{{ $assessment->licenseeTemplate->version }}</td>
                                        <td>{{ $assessment->assessment_date }}</td>
                                        <td class="status">
                                            <span class="badge {{ $assessment->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ __(ucfirst($assessment->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $assessment->imported_rows }}
                                        </td>
                                        <td>
                                            <a href="{{ route('assessments.show', $assessment->id) }}" class="btn btn-sm btn-info">
                                            <i class="ri-eye-fill"></i>
                                            </a>
                                            <a href="#" class="edit-assessment-btn btn btn-sm btn-info" data-id="{{ $assessment->id }}" data-status="{{ $assessment->status }}"><i
                                                                                class="ri-pencil-fill align-bottom text-muted"></i></a>
                                            @if(!in_array($assessment->status, ['completed','archived']))    
                                            <form action="{{ route('assessments.destroy', $assessment->id) }}" method="POST" style="display:inline" >
                                                @csrf @method('DELETE')
                                                <a href="#" class="remove-item-btn btn btn-sm btn-info" onclick="if(confirm('{{ __('Delete this assessment?') }}')) { this.closest('form').submit(); } return false;"><i class="ri-delete-bin-fill align-bottom text-muted"></i></a>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $assessments->links() }}
                            </div>
                            <div class="noresult" style="display: none">
                                <div class="text-center">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px">
                                    </lord-icon>
                                    <h5 class="mt-2">{{ __('No Result Found') }}</h5>
                                    <p class="text-muted mb-0">{{ __('We\'ve searched more than 150+ companies We did not find any companies for you search.') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="pagination-wrap hstack gap-2">
                                <a class="page-item pagination-prev disabled" href="#">
                                    {{ __('Previous') }}
                                </a>
                                <ul class="pagination listjs-pagination mb-0"></ul>
                                <a class="page-item pagination-next" href="#">
                                    {{ __('Next') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    

                   <!-- Edit Assessment Modal -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAssessmentForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Assessment Status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editAssessmentId">

                    <div class="mb-3">
                        <label for="editAssessmentStatus" class="form-label">{{ __('Status') }}</label>
                        <select id="editAssessmentStatus" name="status" class="form-select" required>
                            <option value="draft">{{ __('Draft') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">{{ __('Save Changes') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
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
<script src="{{ URL::asset('build/js/pages/crm-assessments.init.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('editAssessmentModal'));

    // Open modal and fill form
    document.querySelectorAll('.edit-assessment-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const status = this.dataset.status;

            document.getElementById('editAssessmentId').value = id;
            document.getElementById('editAssessmentStatus').value = status;

            editModal.show();
        });
    });

    // Submit form via AJAX
    document.getElementById('editAssessmentForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('editAssessmentId').value;
        const formData = new FormData(this);

        fetch(`/assessments/${id}`, {
            method: 'POST', // Laravel needs POST with _method PUT
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                editModal.hide();
                location.reload();
            } else {
                alert(data.message || '{{ __('Update failed.') }}');
            }
        })
        .catch(err => console.error('Error:', err));
    });

});


</script>
@endsection




