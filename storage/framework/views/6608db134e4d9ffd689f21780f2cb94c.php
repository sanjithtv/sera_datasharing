<?php
App::setLocale(session('lang'));
?>
<?php
    $lang = app()->getLocale();
?>

<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.forms'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
    <link href="<?php echo e(URL::asset('build/libs/sweetalert2/sweetalert2.min.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            ADMINISTRATION
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo app('translator')->get('translation.forms'); ?>
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
                                <a class="btn btn-info add-btn" href="<?php echo e(route('forms.licensee_templates.create')); ?>"><i
                                    class="ri-add-fill me-1 align-bottom"></i> <?php echo app('translator')->get('translation.new_form'); ?></a>
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
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sort" data-sort="name" scope="col">ID</th>
                                        
                                        <th><?php echo app('translator')->get('translation.licensee'); ?></th>
                                        <th><?php echo app('translator')->get('translation.subfolder'); ?></th>
                                        <th><?php echo app('translator')->get('translation.version'); ?></th>
                                        <th><?php echo app('translator')->get('translation.department'); ?></th>
                                        <th><?php echo app('translator')->get('translation.keys'); ?></th>
                                        <th><?php echo app('translator')->get('translation.status'); ?></th>
                                        <th><?php echo app('translator')->get('translation.action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="id" style="display:none;"><a href="javascript:void(0);"
                                                class="fw-medium link-primary"><?php echo e($template->code); ?></a></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="<?php echo e(URL::asset('build/images/brands/dribbble.png')); ?>"
                                                        alt="" class="avatar-xxs rounded-circle image_src object-fit-cover">
                                                </div>
                                                <div class="flex-grow-1 ms-2 name"><?php echo e($template->id); ?>

                                                </div>
                                            </div>
                                        </td>
                                        <td class="owner"><?php echo e($lang === 'ar' ? ($template->licensee->name_ar ?? '—') : ($template->licensee->name_en ?? '—')); ?></td>
                                        <td><?php echo e($lang === 'ar' ? ($template->subfolder->name_ar ?? '—') : ($template->subfolder->name_en ?? '—')); ?> </td>
                                        <td><?php echo e($template->version); ?></td>
                                        <td><?php echo e($lang === 'ar' ? ($template->department->name_ar ?? '—') : ($template->department->name_en ?? '—')); ?></td>
                                        <td><?php echo e($template->keys_count); ?></td>
                                        <td>
                                            <span class="badge <?php echo e($template->status === 'active' ? 'bg-success' : 'bg-secondary'); ?>">
                                                <?php echo e(ucfirst($template->status)); ?>

                                            </span>
                                        </td>
                                        <td>
                        <a href="<?php echo e(route('forms.licensee_templates.edit', $template->id)); ?>" class="edit-item-btn"><i
                                                            class="ri-pencil-fill align-bottom text-muted"></i></a>
                        <form action="<?php echo e(route('forms.licensee_templates.destroy', $template->id)); ?>" method="POST" style="display:inline">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <a class="remove-item-btn" onclick="return confirm('Delete this template?')"><i class="ri-delete-bin-fill align-bottom text-muted"></i></a>
                        </form>
                    </td>







                                   
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
<script src="<?php echo e(URL::asset('build/libs/sweetalert2/sweetalert2.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/js/pages/crm-companies.init.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/modules/licensee_templates/index.blade.php ENDPATH**/ ?>