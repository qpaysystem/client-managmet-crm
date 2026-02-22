<?php $__env->startSection('title', 'Задачи'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Задачи</h1>
    <div>
        <a href="<?php echo e(route('admin.tasks.board')); ?>" class="btn btn-outline-primary me-2"><i class="bi bi-kanban"></i> Канбан-доска</a>
        <a href="<?php echo e(route('admin.tasks.create')); ?>" class="btn btn-primary">Добавить задачу</a>
    </div>
</div>

<form method="get" action="<?php echo e(route('admin.tasks.index')); ?>" class="mb-3">
    <select name="status" class="form-select form-select-sm d-inline-block w-auto">
        <option value="">Все статусы</option>
        <?php $__currentLoopData = \App\Models\Task::statusLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button type="submit" class="btn btn-sm btn-secondary">Показать</button>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Название</th><th>Статус</th><th>Ответственный</th><th>На доске</th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e(Str::limit($t->title, 60)); ?></td>
                    <td><span class="badge bg-secondary"><?php echo e($t->status_label); ?></span></td>
                    <td><?php echo e($t->client ? $t->client->full_name : '—'); ?></td>
                    <td><?php echo e($t->show_on_board ? 'Да' : 'Нет'); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.tasks.edit', $t)); ?>" class="btn btn-sm btn-outline-primary">Изменить</a>
                        <form method="post" action="<?php echo e(route('admin.tasks.destroy', $t)); ?>" class="d-inline" onsubmit="return confirm('Удалить задачу?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="text-muted">Нет задач</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($tasks->hasPages()): ?>
        <div class="card-footer"><?php echo e($tasks->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/tasks/index.blade.php ENDPATH**/ ?>