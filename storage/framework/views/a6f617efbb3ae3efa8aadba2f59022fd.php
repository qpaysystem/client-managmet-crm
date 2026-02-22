<?php $__env->startSection('title', 'Клиенты'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Клиенты</h1>
<form method="get" action="<?php echo e(request()->url()); ?>" class="card card-body mb-4">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Поиск по имени или фамилии" value="<?php echo e(request('search')); ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Все статусы</option>
                <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Активный</option>
                <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Неактивный</option>
            </select>
        </div>
        <?php $__currentLoopData = $customFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(in_array($f->type, ['text', 'number'])): ?>
            <div class="col-md-2">
                <input type="<?php echo e($f->type === 'number' ? 'number' : 'text'); ?>" name="filter_<?php echo e($f->name); ?>" class="form-control" placeholder="<?php echo e($f->label); ?>" value="<?php echo e(request('filter_'.$f->name)); ?>">
            </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Найти</button>
            <a href="<?php echo e(route('frontend.clients.index')); ?>" class="btn btn-outline-secondary">Сбросить</a>
        </div>
    </div>
</form>
<div class="row g-3">
    <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <?php if($c->photo_path): ?>
                <img src="<?php echo e(asset('storage/'.$c->photo_path)); ?>" class="card-img-top" alt="" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="bi bi-person display-4 text-muted"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo e($c->full_name); ?></h5>
                <p class="card-text text-muted small mb-1"><?php echo e($c->email); ?></p>
                <p class="card-text">Баланс: <strong><?php echo e(number_format($c->balance, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></strong></p>
                <a href="<?php echo e(route('frontend.clients.show', $c)); ?>" class="btn btn-outline-primary btn-sm">Подробнее</a>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-12"><p class="text-muted">Клиенты не найдены.</p></div>
    <?php endif; ?>
</div>
<div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="text-muted small">Сортировка:
        <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => 'first_name', 'dir' => 'asc'])); ?>">Имя</a>,
        <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => 'balance', 'dir' => 'desc'])); ?>">Баланс</a>,
        <a href="<?php echo e(request()->fullUrlWithQuery(['sort' => 'registered_at', 'dir' => 'desc'])); ?>">Дата регистрации</a>
    </div>
    <div><?php echo e($clients->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontend', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/frontend/clients/index.blade.php ENDPATH**/ ?>