<?php $__env->startSection('title', 'ТМЦ'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">ТМЦ — Товарно-материальные ценности</h1>
    <a href="<?php echo e(route('admin.products.create')); ?>" class="btn btn-primary">Добавить товар</a>
</div>

<form method="get" action="<?php echo e(route('admin.products.index')); ?>" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Поиск по названию, виду, типу" value="<?php echo e(request('search')); ?>">
        <button type="submit" class="btn btn-secondary">Найти</button>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Фото</th>
                    <th>Название</th>
                    <th>Вид</th>
                    <th>Тип</th>
                    <th class="text-end">Оценочная стоимость</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <?php if($p->photo_path): ?>
                            <img src="<?php echo e(asset('storage/'.$p->photo_path)); ?>" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-image text-muted"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo e(Str::limit($p->name, 50)); ?>

                        <?php if($p->isPledge()): ?><span class="badge bg-warning text-dark ms-1">Залог</span><?php endif; ?>
                    </td>
                    <td><?php echo e($p->kind ?? '—'); ?></td>
                    <td><?php echo e($p->type ?? '—'); ?></td>
                    <td class="text-end"><?php echo e($p->estimated_cost !== null ? number_format($p->estimated_cost, 2) . ' ' . \App\Models\Setting::get('currency', 'RUB') : '—'); ?></td>
                    <td>
                        <a href="<?php echo e(route('admin.products.edit', $p)); ?>" class="btn btn-sm btn-outline-primary">Изменить</a>
                        <form method="post" action="<?php echo e(route('admin.products.destroy', $p)); ?>" class="d-inline" onsubmit="return confirm('Удалить товар?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="text-muted">Нет товаров</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($products->hasPages()): ?>
        <div class="card-footer"><?php echo e($products->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/products/index.blade.php ENDPATH**/ ?>