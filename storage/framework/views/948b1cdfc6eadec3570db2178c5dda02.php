<?php
App::setLocale(session('lang'));
?>

<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.dashboards'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
    <link href="<?php echo e(URL::asset('build/libs/jsvectormap/jsvectormap.min.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('build/libs/swiper/swiper-bundle.min.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col">

            <div class="h-100">
                <div class="row mb-3 pb-1">
                    <div class="col-12">
                        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-16 mb-1">Hello, <?php echo e(Auth::user()->name); ?>!</h4>
                                <p class="text-muted mb-0"><?php echo app('translator')->get('translation.welcome_text'); ?></p>
                            </div>
                            <div class="mt-3 mt-lg-0">
                                <form action="javascript:void(0);">
                                    <div class="row g-3 mb-0 align-items-center">
                                        <div class="col-sm-auto">
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control border-0 dash-filter-picker shadow"
                                                    data-provider="flatpickr" data-range-date="true"
                                                    data-date-format="d M, Y"
                                                    data-deafult-date="01 Jan 2022 to 31 Jan 2022">
                                                <div class="input-group-text bg-primary border-primary text-white">
                                                    <i class="ri-calendar-2-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                       <!--end col-->
                                    </div>
                                    <!--end row-->
                                </form>
                            </div>
                        </div><!-- end card header -->
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->

                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <!-- card -->
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                                            <?php echo app('translator')->get('translation.assessments'); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($totalAssessments); ?>

                                        </h4>
                                        <a href="<?php echo e(route('assessments.index')); ?>" class="text-decoration-underline"><?php echo app('translator')->get('translation.view_assessments'); ?>
                                            </a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle rounded fs-3">
                                            <i class="bx bx-task text-primary"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <!-- card -->
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                                            <?php echo app('translator')->get('translation.forms'); ?></p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($totalForms); ?></h4>
                                        <a href="<?php echo e(route('forms.licensee_templates')); ?>" class="text-decoration-underline"><?php echo app('translator')->get('translation.view_forms'); ?></a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle rounded fs-3">
                                            <i class="bx bx-folder text-info"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <!-- card -->
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                                            <?php echo app('translator')->get('translation.licensees'); ?></p>
                                    </div>
                                    
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($totalLicensees); ?>

                                        </h4>
                                        <a href="<?php echo e(route('licensees.index')); ?>" class="text-decoration-underline"><?php echo app('translator')->get('translation.see_details'); ?></a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle rounded fs-3">
                                            <i class="bx bxs-factory text-primary"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-md-6">
                        <!-- card -->
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">
                                            <?php echo app('translator')->get('translation.departments'); ?></p>
                                    </div>
                                    
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4"><?php echo e($totalDepartments); ?>

                                        </h4>
                                        <a href="<?php echo e(route('departments.index')); ?>" class="text-decoration-underline"><?php echo app('translator')->get('translation.view_all'); ?></a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle rounded fs-3">
                                            <i class="bx bx-sitemap text-info"></i>
                                        </span>
                                    </div>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->
                </div> <!-- end row-->

                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header border-0 align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1"><?php echo app('translator')->get('translation.assessments_by_licensee'); ?></h4>
                            </div><!-- end card header -->

                            <div class="card-body p-0 pb-2">
                                <div class="w-100">
                                    <canvas id="licenseeChart" class="chartjs-chart"
                        data-colors='["--vz-primary-rgb, 0.8", "--vz-primary-rgb, 0.9"]'></canvas>
                                    
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-6">
                        <!-- card -->
                        <div class="card card-height-100">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1"><?php echo app('translator')->get('translation.department_assessments'); ?></h4>
                                
                            </div><!-- end card header -->
                            <div class="card-body p-0 pb-2">
                                <div class="w-100">
                                    <canvas id="departmentChart" class="chartjs-chart"
                        data-colors='["--vz-primary-rgb, 0.8", "--vz-primary-rgb, 0.9"]'></canvas>
                                    
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>

                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1"><?php echo app('translator')->get('translation.template_usage_analytics'); ?></h4>
                            </div><!-- end card header -->

                            <div class="card-body">
                               <canvas id="templateChart" class="chartjs-chart"
                        data-colors='["--vz-primary-rgb, 0.8", "--vz-primary-rgb, 0.9"]'></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Recent <?php echo app('translator')->get('translation.assessments'); ?></h4>
                                
                            </div><!-- end card header -->

                            <div class="card-body">
                                <div class="table-responsive table-card">
                                    <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                        <thead class="text-muted table-light">
                                            <tr>
                                                <th scope="col"><?php echo app('translator')->get('translation.licensee'); ?></th>
                                                <th scope="col"><?php echo app('translator')->get('translation.subfolder'); ?></th>
                                                <th scope="col"><?php echo app('translator')->get('translation.version'); ?></th>
                                                <th><?php echo app('translator')->get('translation.date'); ?></th>
                                                <th scope="col"><?php echo app('translator')->get('translation.status'); ?></th>
                                                <th><?php echo app('translator')->get('translation.entries'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__empty_1 = true; $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $assessment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <tr>
                                                    <td class="licensee"><?php echo e($assessment->licensee->name_en ?? '—'); ?></td>
                                                    <td class="subfolder"><?php echo e($assessment->licenseeTemplate->subfolder->name_en ?? '—'); ?></td>
                                                    <td class="version"><?php echo e($assessment->licenseeTemplate->version); ?></td>
                                                    <td><?php echo e($assessment->assessment_date); ?></td>
                                                    <td class="status">
                                                        <span class="badge <?php echo e($assessment->status === 'active' ? 'bg-success' : 'bg-secondary'); ?>">
                                                            <?php echo e(ucfirst($assessment->status)); ?>

                                                        </span>
                                                    </td>
                                                    <td><?php echo e($assessment->masterData
                ->unique(fn ($row) => $row->entry_counter . '-' . $row->template_sheet_id)
                ->count()); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>    

                                            
                                        </tbody><!-- end tbody -->
                                    </table><!-- end table -->
                                </div>
                            </div>
                        </div> <!-- .card-->
                    </div> <!-- .col-->
                </div> <!-- end row-->

            </div> <!-- end .h-100-->

        </div> <!-- end col -->

       
    </div>
<?php $__env->stopSection(); ?>
<script>
    window.dashboardData = {
        licenseeLabels: <?php echo json_encode($licenseeLabels, 15, 512) ?>,
        licenseeCounts: <?php echo json_encode($licenseeCounts, 15, 512) ?>,
        departmentLabels: <?php echo json_encode($departmentLabels, 15, 512) ?>,
        departmentCounts: <?php echo json_encode($departmentCounts, 15, 512) ?>,
        templateLabels: <?php echo json_encode($templateLabels, 15, 512) ?>,
        templateCounts: <?php echo json_encode($templateCounts, 15, 512) ?>,
    };
</script>
<?php $__env->startSection('script'); ?>
    <!-- apexcharts -->
    <script src="<?php echo e(URL::asset('build/libs/chart.js/chart.umd.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/pages/chartjs.init.js')); ?>"></script>
    <!--<script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/jsvectormap/jsvectormap.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/jsvectormap/maps/world-merc.js')); ?>"></script>-->
    <script src="<?php echo e(URL::asset('build/libs/swiper/swiper-bundle.min.js')); ?>"></script>
    <!-- dashboard init -->
    <script src="<?php echo e(URL::asset('build/js/pages/dashboard-ecommerce.init.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/index.blade.php ENDPATH**/ ?>