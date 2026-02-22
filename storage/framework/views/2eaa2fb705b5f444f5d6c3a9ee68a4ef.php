<?php $__env->startSection('title', 'Транзакции'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">История операций</h1>
<?php $client->load('balanceTransactions.product'); ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Дата</th><th>Тип</th><th>Залог</th><th>Сумма</th><th>Баланс после</th><th>Комментарий</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $client->balanceTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                        <td><?php echo e($t->operation_type_label); ?></td>
                        <td><?php echo e($t->product?->name ?? '—'); ?></td>
                        <td class="<?php echo e($t->type === 'deposit' ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($t->type === 'deposit' ? '+' : '−'); ?><?php echo e(number_format($t->amount, 2)); ?>

                        </td>
                        <td><?php echo e(number_format($t->balance_after, 2)); ?></td>
                        <td class="small text-muted"><?php echo e(Str::limit($t->comment, 40)); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-muted text-center py-4">Нет операций</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/transactions.blade.php ENDPATH**/ ?>