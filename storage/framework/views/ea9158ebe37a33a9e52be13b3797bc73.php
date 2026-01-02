<?php
App::setLocale(session('lang'));
?>

<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.assessments'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
    <link href="<?php echo e(URL::asset('build/libs/sweetalert2/sweetalert2.min.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            SURVEYS
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo app('translator')->get('translation.assessments'); ?>
        <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>
<div class="row">
        <!--end col-->
        <div class="col-xxl-12">
            <div class="card" id="companyList">
                <div class="card-header">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search for <?php echo app('translator')->get('translation.licensee'); ?>...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-auto ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <a class="btn btn-info add-btn" href="<?php echo e(route('assessments.create')); ?>"><i
                                    class="ri-add-fill me-1 align-bottom"></i> <?php echo app('translator')->get('translation.new_assessment'); ?></a>
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
                    <?php if(session('success')): ?>
                        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                    <?php elseif(session('error')): ?>
                        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sort" data-sort="name" scope="col">ID</th>
                                        
                                        <th><?php echo app('translator')->get('translation.licensee'); ?></th>
                                        <th><?php echo app('translator')->get('translation.subfolder'); ?></th>
                                        <th><?php echo app('translator')->get('translation.version'); ?></th>
                                        <th><?php echo app('translator')->get('translation.date'); ?></th>
                                        <th><?php echo app('translator')->get('translation.status'); ?></th>
                                        <th><?php echo app('translator')->get('translation.entries'); ?></th>
                                        <th><?php echo app('translator')->get('translation.action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    <?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $assessment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($assessment->id); ?></td>
                                        <td><?php echo e($assessment->licensee->name_en ?? '—'); ?></td>
                                        <td><?php echo e($assessment->licenseeTemplate->subfolder->name_en ?? '—'); ?></td>
                                        <td><?php echo e($assessment->licenseeTemplate->version); ?></td>
                                        <td><?php echo e($assessment->assessment_date); ?></td>
                                        <td>
                                            <span class="badge <?php echo e($assessment->status === 'active' ? 'bg-success' : 'bg-secondary'); ?>">
                                                <?php echo e(ucfirst($assessment->status)); ?>

                                            </span>
                                        </td>
                                        <td><?php echo e($assessment->masterData
    ->unique(fn ($row) => $row->entry_counter . '-' . $row->template_sheet_id)
    ->count()); ?></td>
                                        <td>
                                            <a href="<?php echo e(route('assessments.show', $assessment->id)); ?>" class="btn btn-sm btn-info">
                                            <i class="ri-eye-fill"></i>
                                            </a>
                                            <a href="#" class="edit-assessment-btn" data-id="<?php echo e($assessment->id); ?>" data-status="<?php echo e($assessment->status); ?>"><i
                                                                                class="ri-pencil-fill align-bottom text-muted"></i></a>
                                                                
                                            <form action="<?php echo e(route('assessments.destroy', $assessment->id)); ?>" method="POST" style="display:inline" >
                                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                <a href="#" class="remove-item-btn" onclick="if(confirm('Delete this assessment?')) { this.closest('form').submit(); } return false;"><i class="ri-delete-bin-fill align-bottom text-muted"></i></a>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    

                   <!-- Edit Assessment Modal -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editAssessmentForm">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Assessment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editAssessmentId">

                    <div class="mb-3">
                        <label for="editAssessmentStatus" class="form-label">Status</label>
                        <select id="editAssessmentStatus" name="status" class="form-select" required>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
<script src="<?php echo e(URL::asset('build/libs/list.js/list.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/list.pagination.js/list.pagination.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/js/pages/crm-companies.init.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/sweetalert2/sweetalert2.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
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
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                editModal.hide();
                location.reload();
            } else {
                alert(data.message || 'Update failed.');
            }
        })
        .catch(err => console.error('Error:', err));
    });
});
</script>
<?php $__env->stopSection(); ?>





<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/modules/assessments/index.blade.php ENDPATH**/ ?>