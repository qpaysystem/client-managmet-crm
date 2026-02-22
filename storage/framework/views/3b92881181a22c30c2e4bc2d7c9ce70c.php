<?php $__env->startSection('title', 'Новое поле'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Добавить поле</h1>
<form method="post" action="<?php echo e(route('admin.custom-fields.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Системное имя (латиница, цифры, _) *</label>
            <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name')); ?>" pattern="[a-z0-9_]+" placeholder="например: city" required>
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Отображаемое название *</label>
            <input type="text" name="label" class="form-control <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('label')); ?>" required>
            <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Тип *</label>
            <select name="type" class="form-select" id="fieldType">
                <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php echo e(old('type') === $k ? 'selected' : ''); ?>><?php echo e($v); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Порядок</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo e(old('sort_order', 0)); ?>" min="0">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="required" value="1" class="form-check-input" id="required" <?php echo e(old('required') ? 'checked' : ''); ?>>
                <label class="form-check-label" for="required">Обязательное поле</label>
            </div>
        </div>
        <div class="col-12" id="optionsWrap" style="display:<?php echo e(old('type') === 'select' ? 'block' : 'none'); ?>;">
            <label class="form-label">Варианты для выпадающего списка (каждый с новой строки)</label>
            <textarea name="options" class="form-control" rows="4" placeholder="Вариант 1\nВариант 2"><?php echo e(old('options')); ?></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Создать</button>
        <a href="<?php echo e(route('admin.custom-fields.index')); ?>" class="btn btn-secondary">Отмена</a>
    </div>
</form>
<script>
document.getElementById('fieldType').addEventListener('change', function() {
    document.getElementById('optionsWrap').style.display = this.value === 'select' ? 'block' : 'none';
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/custom-fields/create.blade.php ENDPATH**/ ?>