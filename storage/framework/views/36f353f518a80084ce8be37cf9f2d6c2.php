<?php $name = 'custom_'.$field->name; $id = 'custom_'.$field->id; ?>
<label class="form-label"><?php echo e($field->label); ?> <?php if($field->required): ?><span class="text-danger">*</span><?php endif; ?></label>
<?php if($field->type === 'text'): ?>
    <input type="text" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-control" value="<?php echo e($value ?? ''); ?>" <?php if($field->required): ?> required <?php endif; ?>>
<?php elseif($field->type === 'number'): ?>
    <input type="number" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-control" value="<?php echo e($value ?? ''); ?>" <?php if($field->required): ?> required <?php endif; ?>>
<?php elseif($field->type === 'date'): ?>
    <input type="date" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-control" value="<?php echo e($value ?? ''); ?>" <?php if($field->required): ?> required <?php endif; ?>>
<?php elseif($field->type === 'select'): ?>
    <select name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-select" <?php if($field->required): ?> required <?php endif; ?>>
        <option value="">— Выберите —</option>
        <?php $__currentLoopData = $field->options ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($opt); ?>" <?php echo e(($value ?? '') == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
<?php elseif($field->type === 'checkbox'): ?>
    <div class="form-check">
        <input type="checkbox" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-check-input" value="1" <?php echo e(($value ?? '') == '1' ? 'checked' : ''); ?>>
        <label class="form-check-label" for="<?php echo e($id); ?>">Да</label>
    </div>
<?php elseif($field->type === 'textarea'): ?>
    <textarea name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-control" rows="3" <?php if($field->required): ?> required <?php endif; ?>><?php echo e($value ?? ''); ?></textarea>
<?php else: ?>
    <input type="file" name="<?php echo e($name); ?>" id="<?php echo e($id); ?>" class="form-control" <?php if($field->required): ?> required <?php endif; ?>>
<?php endif; ?>
<?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/clients/_custom_field.blade.php ENDPATH**/ ?>