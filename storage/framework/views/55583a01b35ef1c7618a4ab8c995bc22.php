<?php $__env->startSection('title', 'Клиенты'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Клиенты</h1>
    <a href="<?php echo e(route('admin.clients.create')); ?>" class="btn btn-primary">Добавить клиента</a>
</div>
<form method="get" class="row g-2 mb-4">
    <div class="col-auto">
        <input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?php echo e(request('search')); ?>">
    </div>
    <div class="col-auto">
        <select name="status" class="form-select">
            <option value="">Все статусы</option>
            <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Активный</option>
            <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Неактивный</option>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-secondary">Найти</button></div>
</form>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Баланс</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($c->id); ?></td>
                    <td><?php echo e($c->full_name); ?></td>
                    <td><?php echo e($c->email); ?></td>
                    <td><?php echo e($c->phone); ?></td>
                    <td><?php echo e(number_format($c->balance, 2)); ?></td>
                    <td><span class="badge bg-<?php echo e($c->status === 'active' ? 'success' : 'secondary'); ?>"><?php echo e($c->status === 'active' ? 'Активный' : 'Неактивный'); ?></span></td>
                    <td>
                        <a href="<?php echo e(route('admin.clients.show', $c)); ?>" class="btn btn-sm btn-outline-primary">Открыть</a>
                        <a href="<?php echo e(route('admin.clients.edit', $c)); ?>" class="btn btn-sm btn-outline-secondary">Изменить</a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="text-muted">Нет клиентов</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?php echo e($clients->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/clients/index.blade.php ENDPATH**/ ?>