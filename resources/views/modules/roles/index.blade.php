@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    @lang('translation.roles')
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
            @lang('translation.roles')
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
                                <input type="text" class="form-control search" placeholder="Search for @lang('translation.roles')...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sort" data-sort="name" scope="col">@lang('translation.id')</th>
                                        <th class="sort" data-sort="owner" scope="col">@lang('translation.name')</th>
                                        <th class="sort" data-sort="owner" scope="col">@lang('translation.permissions')</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach($roles as $key => $role)
                                    <tr>
                                        <td>
                                                <div class="flex-grow-1 ms-2 name">{{$role->id}}
                                                </div>
                                        </td>
                                        <td class="owner"><strong>{{ ucfirst($role->name) }}</td>
                                        <td class="email">{{count($role->permissions)}}</td>
                                        
                                        <td>
                                            <ul class="list-inline hstack gap-2 mb-0">
                                                <li class="list-inline-item" data-bs-toggle="tooltip"
                                                    data-bs-trigger="hover" data-bs-placement="top" title="View">
                                                    <a href="{{ route('security.roles.permissions.edit', $role->id) }}" class="view-item-btn"><i
                                                            class="ri-eye-fill align-bottom text-muted"></i></a>
                                                </li>

                                                <li class="list-inline-item" data-bs-toggle="tooltip"
                                                    data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                    <a href="#"  class="edit-item-btn" data-id="{{ $role->id }}" data-name="{{ $role->name }}" data-bs-toggle="modal"  data-bs-target=" #editRoleModal"> <i class="ri-pencil-fill align-bottom text-muted"></i></a>
</a>
                                                    
                                                </li>
                                                <li class="list-inline-item" data-bs-toggle="tooltip"
                                                    data-bs-trigger="hover" data-bs-placement="top" title="Delete">
                                                    <a class="remove-item-btn" data-bs-toggle="modal"
                                                        href="#deleteRecordModal">
                                                        <i class="ri-delete-bin-fill align-bottom text-muted"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </td>
                                        
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="noresult" style="display: none">
                                <div class="text-center">
                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px">
                                    </lord-icon>
                                    <h5 class="mt-2">Sorry! No Result Found</h5>
                                    <p class="text-muted mb-0">We've searched more than 150+ companies
                                        We did not find any
                                        companies for you search.</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div class="pagination-wrap hstack gap-2">
                                <a class="page-item pagination-prev disabled" href="#">
                                    Previous
                                </a>
                                <ul class="pagination listjs-pagination mb-0"></ul>
                                <a class="page-item pagination-next" href="#">
                                    Next
                                </a>
                            </div>
                        </div>
                    </div>
                    

                    <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close"
                                        id="btn-close"></button>
                                </div>
                                <div class="modal-body p-5 text-center">
                                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                                        colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                                    </lord-icon>
                                    <div class="mt-4 text-center">
                                        <h4 class="fs-semibold">You are about to delete a company ?</h4>
                                        <p class="text-muted fs-14 mb-4 pt-1">Deleting your company will
                                            remove all of your information from our database.</p>
                                        <div class="hstack gap-2 justify-content-center remove">
                                            <button class="btn btn-link link-success fw-medium text-decoration-none"
                                                data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i>
                                                Close</button>
                                            <button class="btn btn-danger" id="delete-record">Yes,
                                                Delete It!!</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end delete modal -->
                    <!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editRoleForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="editRoleModalLabel">Edit Role Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="name" placeholder="Enter role name" required>
                        <div class="invalid-feedback">Role name is required.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
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
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/list.pagination.js/list.pagination.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
    let editForm = document.getElementById('editRoleForm');
    let roleNameInput = document.getElementById('roleName');
    let currentRoleId = null;

    // When clicking edit button, fill modal fields
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentRoleId = this.dataset.id;
            roleNameInput.value = this.dataset.name;
            editForm.action = `/security/roles/${currentRoleId}`; // Adjust route if different
        });
    });

    // Handle form submission via AJAX
    editForm.addEventListener('submit', function (e) {
        e.preventDefault();

        fetch(this.action, {
            method: 'POST',
            headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest', // 👈 Add this line
        'Accept': 'application/json'
    },
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            console.log(data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Role name updated successfully.',
                    timer: 1500,
                    showConfirmButton: false
                });
                editModal.hide();
                // Update the table row instantly
                document.querySelector(`a[data-id="${currentRoleId}"]`).closest('tr').querySelector('.owner strong').textContent = data.role.name;
            } else {
                Swal.fire('Error', data.message || 'Something went wrong', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Unable to update role', 'error');
            console.error(err);
        });
    });
});
</script>

@endsection
