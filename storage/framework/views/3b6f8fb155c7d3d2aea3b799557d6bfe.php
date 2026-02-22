<?php $__env->startSection('title', 'Проекты'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Проекты</h1>
    <a href="<?php echo e(route('admin.projects.create')); ?>" class="btn btn-primary">Создать проект</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Название</th>
                    <th>Статей расхода</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <a href="<?php echo e(route('admin.projects.show', $p)); ?>" class="fw-medium"><?php echo e($p->name); ?></a>
                    </td>
                    <td><?php echo e($p->expense_items_count); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.projects.show', $p)); ?>" class="btn btn-sm btn-outline-primary">Карточка</a>
                        <a href="<?php echo e(route('admin.projects.edit', $p)); ?>" class="btn btn-sm btn-outline-secondary">Изменить</a>
                        <form method="post" action="<?php echo e(route('admin.projects.destroy', $p)); ?>" class="d-inline" onsubmit="return confirm('Удалить проект и все статьи расхода?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="3" class="text-muted">Нет проектов. Создайте первый проект.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($projects->hasPages()): ?>
        <div class="card-footer"><?php echo e($projects->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/projects/index.blade.php ENDPATH**/ ?>