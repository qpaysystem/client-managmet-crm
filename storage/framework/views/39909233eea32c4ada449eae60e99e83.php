<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Всего клиентов</h6>
                <h3 class="mb-0"><?php echo e($stats['clients_total']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Активных</h6>
                <h3 class="mb-0"><?php echo e($stats['clients_active']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Общий баланс</h6>
                <h3 class="mb-0"><?php echo e(number_format($stats['balance_total'], 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Баланс товаров (ТМЦ)</h6>
                <h3 class="mb-0"><?php echo e(number_format($stats['products_balance'], 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></h3>
                <small class="text-muted"><?php echo e($stats['products_count']); ?> позиций в ТМЦ</small>
            </div>
        </div>
    </div>
</div>
<h5 class="mb-3">Последние клиенты</h5>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Имя</th><th>Email</th><th>Баланс</th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $recentClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($c->full_name); ?></td>
                    <td><?php echo e($c->email); ?></td>
                    <td><?php echo e(number_format($c->balance, 2)); ?></td>
                    <td><a href="<?php echo e(route('admin.clients.show', $c)); ?>" class="btn btn-sm btn-outline-primary">Открыть</a></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="4" class="text-muted">Нет клиентов</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>