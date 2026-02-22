<?php $__env->startSection('title', 'Транзакции'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Журнал транзакций</h1>

<form method="get" action="<?php echo e(route('admin.transactions.index')); ?>" class="row g-2 mb-4">
    <div class="col-auto">
        <label class="form-label visually-hidden">Клиент</label>
        <select name="client_id" class="form-select">
            <option value="">Все клиенты</option>
            <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c->id); ?>" <?php if(request('client_id') == $c->id): echo 'selected'; endif; ?>><?php echo e($c->full_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Тип операции</label>
        <select name="operation_type" class="form-select">
            <option value="">Все типы</option>
            <?php $__currentLoopData = \App\Models\BalanceTransaction::operationTypeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($value); ?>" <?php if(request('operation_type') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Тип (пополнение/списание)</label>
        <select name="type" class="form-select">
            <option value="">Все</option>
            <option value="deposit" <?php if(request('type') === 'deposit'): echo 'selected'; endif; ?>>Пополнение</option>
            <option value="withdraw" <?php if(request('type') === 'withdraw'): echo 'selected'; endif; ?>>Списание</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Дата с</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo e(request('date_from')); ?>" placeholder="С">
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">По</label>
        <input type="date" name="date_to" class="form-control" value="<?php echo e(request('date_to')); ?>" placeholder="По">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary me-1"><i class="bi bi-search"></i> Показать</button>
        <a href="<?php echo e(route('admin.transactions.index')); ?>" class="btn btn-outline-secondary">Сбросить</a>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Дата и время</th>
                        <th>Клиент</th>
                        <th>Тип операции</th>
                        <th>Залог</th>
                        <th>Дней</th>
                        <th>Дата возврата</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">Баланс после</th>
                        <th>Оператор</th>
                        <th>Комментарий</th>
                        <th class="text-center" style="width: 100px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.clients.show', $t->client)); ?>"><?php echo e($t->client->full_name); ?></a>
                        </td>
                        <td><?php echo e($t->operation_type_label); ?></td>
                        <td><?php if($t->product): ?><a href="<?php echo e(route('admin.products.edit', $t->product)); ?>"><?php echo e(Str::limit($t->product->name, 25)); ?></a><?php else: ?> — <?php endif; ?></td>
                        <td><?php echo e($t->loan_days ?? '—'); ?></td>
                        <td><?php echo e($t->loan_due_at?->format('d.m.Y') ?? '—'); ?></td>
                        <td class="text-end <?php echo e($t->type === 'deposit' ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($t->type === 'deposit' ? '+' : '−'); ?><?php echo e(number_format($t->amount, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?>

                        </td>
                        <td class="text-end"><?php echo e(number_format($t->balance_after, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></td>
                        <td><?php echo e($t->user?->name ?? '—'); ?></td>
                        <td class="text-muted small"><?php echo e(Str::limit($t->comment, 50)); ?></td>
                        <td class="text-center">
                            <form method="post" action="<?php echo e(route('admin.transactions.destroy', $t)); ?>" class="d-inline" onsubmit="return confirm('Удалить эту транзакцию? Баланс клиента будет пересчитан.');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить транзакцию"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Нет операций за выбранный период</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($transactions->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($transactions->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/transactions/index.blade.php ENDPATH**/ ?>