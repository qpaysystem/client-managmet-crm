<?php $__env->startSection('title', 'Товары'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Товары</h1>

<div class="row g-3">
    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <?php if($p->photo_path): ?>
                <img src="<?php echo e(asset('storage/'.$p->photo_path)); ?>" class="card-img-top" alt="<?php echo e($p->name); ?>" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="bi bi-box-seam display-4 text-muted"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo e($p->name); ?></h5>
                <?php if($p->kind || $p->type): ?>
                    <p class="card-text text-muted small mb-1">
                        <?php if($p->kind): ?> <?php echo e($p->kind); ?> <?php endif; ?>
                        <?php if($p->kind && $p->type): ?> · <?php endif; ?>
                        <?php if($p->type): ?> <?php echo e($p->type); ?> <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if($p->estimated_cost !== null): ?>
                    <p class="card-text mb-1">Оценочная стоимость: <strong><?php echo e(number_format($p->estimated_cost, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></strong></p>
                <?php endif; ?>
                <?php if($p->description): ?>
                    <p class="card-text small"><?php echo e(Str::limit($p->description, 100)); ?></p>
                <?php endif; ?>
                <a href="<?php echo e(route('frontend.products.show', $p)); ?>" class="btn btn-outline-primary btn-sm">Подробнее</a>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-12"><p class="text-muted">Товары не найдены.</p></div>
    <?php endif; ?>
</div>

<div class="mt-4"><?php echo e($products->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontend', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/frontend/products/index.blade.php ENDPATH**/ ?>