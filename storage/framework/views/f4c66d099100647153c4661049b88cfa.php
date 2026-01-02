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
                
                <div class="card-body">
                    <div>

    
    <form method="POST" action="<?php echo e(route('forms.licensee_templates.update')); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <input type="hidden" name="licenseeTemplate_id" value="<?php echo e($licenseeTemplate->id); ?>">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label><?php echo app('translator')->get('translation.licensee'); ?></label>
                <select name="licensee_id" class="form-select">
                    <?php $__currentLoopData = $licensees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($id); ?>" <?php echo e($licenseeTemplate->licensee_id == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label><?php echo app('translator')->get('translation.subfolder'); ?></label>
                <select name="subfolder_id" class="form-select">
                    <?php $__currentLoopData = $subfolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($id); ?>" <?php echo e($licenseeTemplate->subfolder_id == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label><?php echo app('translator')->get('translation.version'); ?></label>
                <input type="text" name="version" value="<?php echo e($licenseeTemplate->version); ?>" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label><?php echo app('translator')->get('translation.department'); ?></label>
                <select name="department_id" class="form-select">
                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($id); ?>" <?php echo e($licenseeTemplate->department_id == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label><?php echo app('translator')->get('translation.sheetname'); ?></label>
                <input type="text" name="sheet_name" value="<?php echo e($licenseeTemplate->sheet_name); ?>" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <label><?php echo app('translator')->get('translation.status'); ?></label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo e($licenseeTemplate->status == 'active' ? 'selected' : ''); ?>>Active</option>
                    <option value="inactive" <?php echo e($licenseeTemplate->status == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button class="btn btn-success mb-3"><?php echo app('translator')->get('translation.update_template'); ?></button>
    </form>

    <hr>

    
    <h4 class="mt-4"><?php echo app('translator')->get('translation.template_keys'); ?></h4>
    <form method="POST" action="<?php echo e(route('forms.licensee_templates.keys.store', $licenseeTemplate->id)); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="licensee_template_id" value="<?php echo e($licenseeTemplate->id); ?>">
        <input type="hidden" name="licensee_id" value="<?php echo e($licenseeTemplate->licensee_id); ?>">
        <div class="row align-items-end">
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.sheetname'); ?></label>
                <select name="sheet_id" class="form-select">
                    <?php $__currentLoopData = $sheets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sheetId => $keys): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sheetId); ?>"><?php echo e($keys); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.code'); ?></label>
                <input name="short_code" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.description'); ?> (EN)</label>
                <input name="desc_en" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.description'); ?> (AR)</label>
                <input name="desc_ar" class="form-control">
            </div>
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.mandatory'); ?></label>
                <select name="mandatory" class="form-select">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label><?php echo app('translator')->get('translation.type'); ?></label>
                <select name="type" class="form-select" required>
                    <option value="text">Text</option>
                    <option value="number">Integer</option>
                    <option value="number_percentage">Number Percentage</option>
                    <option value="date">Date</option>
                    <option value="datetime">DateTime</option>
                    <option value="time">Time</option>
                </select>
            </div>
            <input type="hidden" name="licensee_id" value="<?php echo e($licenseeTemplate->licensee_id); ?>">
        </div>
        <button class="btn btn-primary mt-3"><?php echo app('translator')->get('translation.add_key'); ?></button>
    </form>

    
    <table class="table table-bordered mt-4" id="keysTable">
        <thead class="table-light">
            <tr>
                <th><?php echo app('translator')->get('translation.code'); ?></th>
                <th>EN</th>
                <th>AR</th>
                <th><?php echo app('translator')->get('translation.mandatory'); ?></th>
                <th><?php echo app('translator')->get('translation.type'); ?></th>
                <th><?php echo app('translator')->get('translation.action'); ?></th>
            </tr>
        </thead>
        <?php
        $groupedKeys = $templateKeys->groupBy('sheet_id');
        ?>
        <tbody>
            <?php $__currentLoopData = $groupedKeys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sheetId => $keys): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light fw-bold">
                Sheet: <?php echo e($keys->first()->sheet->sheet_name ?? 'Unknown Sheet'); ?>

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
                        <?php $__currentLoopData = $keys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <form method="POST" action="<?php echo e(route('forms.licensee_templates.keys.update', $key->id)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>

                                    <td>
                                        <input type="text" name="short_code" value="<?php echo e($key->short_code); ?>" class="form-control" required>
                                    </td>

                                    <td>
                                        <input type="text" name="desc_en" value="<?php echo e($key->desc_en); ?>" class="form-control" required>
                                    </td>

                                    <td>
                                        <input type="text" name="desc_ar" value="<?php echo e($key->desc_ar); ?>" class="form-control">
                                    </td>

                                    <td>
                                        <select name="mandatory" class="form-select">
                                            <option value="1" <?php echo e($key->mandatory ? 'selected' : ''); ?>>Yes</option>
                                            <option value="0" <?php echo e(!$key->mandatory ? 'selected' : ''); ?>>No</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select name="type" class="form-select">
                                            <option value="text" <?php echo e($key->type == 'text' ? 'selected' : ''); ?>>Text</option>
                                            <option value="number" <?php echo e($key->type == 'number' ? 'selected' : ''); ?>>Number</option>
                                            <option value="select" <?php echo e($key->type == 'select' ? 'selected' : ''); ?>>Select</option>
                                            <option value="number_percentage" <?php echo e($key->type == 'number_percentage' ? 'selected' : ''); ?>>Number Percentage</option>
                                            <option value="date" <?php echo e($key->type == 'date' ? 'selected' : ''); ?>>Date</option>
                                            <option value="datetime" <?php echo e($key->type == 'datetime' ? 'selected' : ''); ?>>Datetime</option>
                                            <option value="time" <?php echo e($key->type == 'time' ? 'selected' : ''); ?>>Time</option>
                                        </select>
                                    </td>

                                    <td>
                                        <button class="btn btn-sm btn-success">Update</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>



<div class="modal fade" id="editKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editKeyForm" name="editKeyForm">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
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
    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
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
                headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/sera_datasharing/resources/views/modules/licensee_templates/edit.blade.php ENDPATH**/ ?>