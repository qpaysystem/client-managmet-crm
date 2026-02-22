<?php $__env->startSection('title', $project->name); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h4 mb-1"><?php echo e($project->name); ?></h1>
        <?php if($project->description): ?>
            <p class="text-muted small mb-0"><?php echo e(Str::limit($project->description, 200)); ?></p>
        <?php endif; ?>
    </div>
    <div>
        <a href="<?php echo e(route('admin.projects.edit', $project)); ?>" class="btn btn-outline-primary">Изменить</a>
        <form method="post" action="<?php echo e(route('admin.projects.destroy', $project)); ?>" class="d-inline" onsubmit="return confirm('Удалить проект и все статьи расхода?');">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-outline-danger">Удалить</button>
        </form>
    </div>
</div>


<h5 class="mb-3">Сводка по расходам</h5>
<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Статья расхода</th>
                    <th class="text-end">Кол-во операций</th>
                    <th class="text-end">Сумма, <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $summaryByItem; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($row['item']->name); ?></td>
                    <td class="text-end"><?php echo e($row['count']); ?></td>
                    <td class="text-end"><?php echo e(number_format($row['total'], 2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="3" class="text-muted">Нет расходов по статьям. Добавьте статьи расхода и создавайте операции «Расход на проект» у клиентов.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if($summaryByItem->isNotEmpty()): ?>
            <tfoot class="table-light">
                <tr>
                    <th>Итого</th>
                    <th class="text-end"><?php echo e($project->balanceTransactions->count()); ?></th>
                    <th class="text-end"><?php echo e(number_format($grandTotal, 2)); ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>


<h5 class="mb-3">Статьи расхода на проект</h5>
<div class="card mb-4">
    <div class="card-body">
        <form method="post" action="<?php echo e(route('admin.projects.expense-items.store', $project)); ?>" class="row g-2 align-items-end mb-3">
            <?php echo csrf_field(); ?>
            <div class="col-auto flex-grow-1">
                <label class="form-label small mb-0">Новая статья</label>
                <input type="text" name="name" class="form-control form-control-sm" placeholder="Название статьи расхода" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Добавить</button>
            </div>
        </form>
        <ul class="list-group list-group-flush">
            <?php $__empty_1 = true; $__currentLoopData = $project->expenseItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span><?php echo e($item->name); ?></span>
                <form method="post" action="<?php echo e(route('admin.projects.expense-items.destroy', [$project, $item])); ?>" class="d-inline" onsubmit="return confirm('Удалить статью расхода?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                </form>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <li class="list-group-item text-muted">Нет статей. Добавьте статью расхода выше.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>


<h5 class="mb-3">Операции по проекту</h5>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Статья расхода</th>
                    <th class="text-end">Сумма</th>
                    <th>Комментарий</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $project->balanceTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                    <td><a href="<?php echo e(route('admin.clients.show', $t->client)); ?>"><?php echo e($t->client->full_name); ?></a></td>
                    <td><?php echo e($t->projectExpenseItem?->name ?? '—'); ?></td>
                    <td class="text-end"><?php echo e(number_format($t->amount, 2)); ?></td>
                    <td><?php echo e(Str::limit($t->comment, 40)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="text-muted">Нет операций. Создайте операцию «Расход на проект» в карточке клиента.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="mt-3 mb-0"><a href="<?php echo e(route('admin.projects.index')); ?>" class="btn btn-secondary">← К списку проектов</a></p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/projects/show.blade.php ENDPATH**/ ?>