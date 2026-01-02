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
                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1"></h4>
                                    
                                </div>
                <div class="card-body">
                    <div class="live-preview">

                        <form action="<?php echo e(route('assessments.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="row gy-4">
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label><?php echo app('translator')->get('translation.licensee'); ?></label>
                                    <select name="licensee_id" class="form-select" required>
                                        <option value=""><?php echo app('translator')->get('translation.select'); ?></option>
                                        <?php $__currentLoopData = $licensees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $licensee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($licensee->id); ?>"><?php echo e($licensee->name_en); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-xxl-6 col-md-6">
                                <label><?php echo app('translator')->get('translation.template'); ?></label>
                                <select name="licensee_template_id" class="form-select" required>
                                    <option value=""><?php echo app('translator')->get('translation.select'); ?></option>
                                    <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($template->id); ?>"><?php echo e($template->display_name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label><?php echo app('translator')->get('translation.assessment_date'); ?></label>
                                <input type="date" name="assessment_date" class="form-control" required>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label><?php echo app('translator')->get('translation.status'); ?></label>
                                <select name="status" class="form-select">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                </select>
                            </div>

                            <div class="mb-3 col-xxl-6 col-md-6">
                                <label><?php echo app('translator')->get('translation.data_entry_mode'); ?></label>
                                <select name="entry_mode" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="excel">Excel Upload</option>
                                    <option value="manual">Manual Entry</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Continue</button>
                            <a href="<?php echo e(route('assessments.index')); ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                        </form>
                        
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
<?php $__env->stopSection(); ?>






















<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/modules/assessments/create.blade.php ENDPATH**/ ?>