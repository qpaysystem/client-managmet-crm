<?php $__env->startSection('title', $client->full_name); ?>
<?php $__env->startSection('content'); ?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo e(route('frontend.clients.index')); ?>">Клиенты</a></li>
        <li class="breadcrumb-item active"><?php echo e($client->full_name); ?></li>
    </ol>
</nav>
<div class="row">
    <div class="col-md-4 mb-4">
        <?php if($client->photo_path): ?>
            <img src="<?php echo e(asset('storage/'.$client->photo_path)); ?>" alt="<?php echo e($client->full_name); ?>" class="img-fluid rounded shadow">
        <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center py-5">
                <i class="bi bi-person display-1 text-muted"></i>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 card-title"><?php echo e($client->full_name); ?></h1>
                <span class="badge bg-<?php echo e($client->status === 'active' ? 'success' : 'secondary'); ?> mb-3"><?php echo e($client->status === 'active' ? 'Активный' : 'Неактивный'); ?></span>
                <table class="table table-borderless mb-0">
                    <tr><th style="width:180px">Email</th><td><?php echo e($client->email); ?></td></tr>
                    <tr><th>Телефон</th><td><?php echo e($client->phone); ?></td></tr>
                    <tr><th>Дата рождения</th><td><?php echo e($client->birth_date?->format('d.m.Y')); ?></td></tr>
                    <tr><th>Дата регистрации</th><td><?php echo e($client->registered_at?->format('d.m.Y')); ?></td></tr>
                    <tr><th>Баланс</th><td><strong><?php echo e(number_format($client->balance, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></strong></td></tr>
                </table>
                <?php $__currentLoopData = $client->customValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($cv->customField && ($cv->value !== null && $cv->value !== '')): ?>
                    <p class="mb-1"><strong><?php echo e($cv->customField->label); ?>:</strong> <?php echo e($cv->value); ?></p>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">История операций с балансом</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Дата</th><th>Тип</th><th>Сумма</th><th>Баланс после</th></tr></thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $client->balanceTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                            <td><?php echo e($t->type === 'deposit' ? 'Пополнение' : 'Списание'); ?></td>
                            <td><?php echo e($t->type === 'deposit' ? '+' : '-'); ?><?php echo e(number_format($t->amount, 2)); ?></td>
                            <td><?php echo e(number_format($t->balance_after, 2)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-muted">Нет операций</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<a href="<?php echo e(route('frontend.clients.index')); ?>" class="btn btn-secondary mt-3">← К списку клиентов</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontend', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/frontend/clients/show.blade.php ENDPATH**/ ?>