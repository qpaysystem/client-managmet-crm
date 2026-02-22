<?php $__env->startSection('title', $product->name); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-4">
        <?php if($product->photo_path): ?>
            <img src="<?php echo e(asset('storage/'.$product->photo_path)); ?>" alt="<?php echo e($product->name); ?>" class="img-fluid rounded shadow-sm">
        <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 280px;">
                <i class="bi bi-box-seam display-1 text-muted"></i>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <h1 class="h4 mb-3"><?php echo e($product->name); ?></h1>
        <dl class="row mb-0">
            <?php if($product->kind): ?>
                <dt class="col-sm-4 text-muted">Вид</dt>
                <dd class="col-sm-8"><?php echo e($product->kind); ?></dd>
            <?php endif; ?>
            <?php if($product->type): ?>
                <dt class="col-sm-4 text-muted">Тип</dt>
                <dd class="col-sm-8"><?php echo e($product->type); ?></dd>
            <?php endif; ?>
            <?php if($product->estimated_cost !== null): ?>
                <dt class="col-sm-4 text-muted">Оценочная стоимость</dt>
                <dd class="col-sm-8"><strong><?php echo e(number_format($product->estimated_cost, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></strong></dd>
            <?php endif; ?>
        </dl>
        <?php if($product->description): ?>
            <hr>
            <p class="text-muted"><?php echo e(nl2br(e($product->description))); ?></p>
        <?php endif; ?>
        <a href="<?php echo e(route('frontend.products.index')); ?>" class="btn btn-outline-secondary mt-3">← К списку товаров</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontend', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/frontend/products/show.blade.php ENDPATH**/ ?>