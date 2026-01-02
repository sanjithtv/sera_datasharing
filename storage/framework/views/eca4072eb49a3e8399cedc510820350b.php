<?php
App::setLocale(session('lang'));
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
                            <label>Create <?php echo app('translator')->get('translation.forms'); ?></label>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($e); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div>
                        <form action="<?php echo e(route('forms.licensee_templates.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.licensee'); ?> <span class="text-danger">*</span></label>
                    <select name="licensee_id" class="form-select" required>
                        <option value="">Select Licensee</option>
                        <?php $__currentLoopData = $licensees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>" <?php echo e(old('licensee_id') == $id ? 'selected' : ''); ?>>
                                <?php echo e($name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.subfolder'); ?> <span class="text-danger">*</span></label>
                    <select name="subfolder_id" class="form-select" required>
                        <option value="">Select Subfolder</option>
                        <?php $__currentLoopData = $subfolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>" <?php echo e(old('subfolder_id') == $id ? 'selected' : ''); ?>>
                                <?php echo e($name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.version'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="version" value="<?php echo e(old('version')); ?>" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.department'); ?> <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($id); ?>" <?php echo e(old('department_id') == $id ? 'selected' : ''); ?>>
                                <?php echo e($name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.sheetname'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="sheet_name" value="<?php echo e(old('sheet_name')); ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?php echo app('translator')->get('translation.status'); ?> <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" <?php echo e(old('status') == 'active' ? 'selected' : ''); ?>>Active</option>
                        <option value="inactive" <?php echo e(old('status') == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                    </select>
                </div>
        </div>
        <br>
        <div class="d-flex justify-content-between">
                    <a href="<?php echo e(route('forms.licensee_templates')); ?>" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Next → Add Keys</button>
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
<script src="<?php echo e(URL::asset('build/libs/sweetalert2/sweetalert2.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/modules/licensee_templates/create.blade.php ENDPATH**/ ?>