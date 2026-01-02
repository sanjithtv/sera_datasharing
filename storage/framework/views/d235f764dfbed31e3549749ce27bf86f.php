<?php echo $__env->yieldContent('css'); ?>
<!-- Layout config Js -->
<script src="<?php echo e(URL::asset('build/js/layout.js')); ?>"></script>
<?php if(session('lang')=="ar"): ?>
<link href="<?php echo e(URL::asset('build/css/bootstrap.min.rtl.css')); ?>" id="bootstrap-style" rel="stylesheet" type="text/css" />
<link href="<?php echo e(URL::asset('build/css/app.min.rtl.css')); ?>" id="app-style" rel="stylesheet" type="text/css" />
<?php else: ?>
<link href="<?php echo e(URL::asset('build/css/bootstrap.min.css')); ?>" id="bootstrap-style" rel="stylesheet" type="text/css" />
<link href="<?php echo e(URL::asset('build/css/app.min.css')); ?>" id="app-style" rel="stylesheet" type="text/css" />
<?php endif; ?>
<!-- Icons Css -->
<link href="<?php echo e(URL::asset('build/css/icons.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- custom Css-->
<link href="<?php echo e(URL::asset('build/css/custom.min.css')); ?>" id="app-style" rel="stylesheet" type="text/css" />
<?php /**PATH /var/www/html/sera_datasharing/resources/views/layouts/head-css.blade.php ENDPATH**/ ?>