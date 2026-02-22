<?php $__env->startSection('title', 'Поля клиентов'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Поля карточки клиента</h1>
    <a href="<?php echo e(route('admin.custom-fields.create')); ?>" class="btn btn-primary">Добавить поле</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Системное имя</th><th>Название</th><th>Тип</th><th>Обязательное</th><th>Активно</th><th></th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($f->sort_order); ?></td>
                    <td><code><?php echo e($f->name); ?></code></td>
                    <td><?php echo e($f->label); ?></td>
                    <td><?php echo e($f->type); ?></td>
                    <td><?php echo e($f->required ? 'Да' : 'Нет'); ?></td>
                    <td><?php echo e($f->is_active ? 'Да' : 'Нет'); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.custom-fields.edit', $f)); ?>" class="btn btn-sm btn-outline-primary">Изменить</a>
                        <form method="post" action="<?php echo e(route('admin.custom-fields.destroy', $f)); ?>" class="d-inline" onsubmit="return confirm('Удалить поле?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php if($fields->isEmpty()): ?>
<p class="text-muted mt-3">Нет пользовательских полей. Добавьте поля для расширения карточки клиента.</p>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/custom-fields/index.blade.php ENDPATH**/ ?>