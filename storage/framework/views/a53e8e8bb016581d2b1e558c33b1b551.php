<?php $__env->startSection('title', 'Проекты'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Проекты</h1>
<p class="text-muted small mb-3">Выберите проект, чтобы увидеть сводку по расходам, транзакции и данные по клиентам.</p>

<div class="row g-3">
    <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="col-md-6 col-lg-4">
        <a href="<?php echo e(route('cabinet.projects.show', $project)); ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-dark mb-1"><?php echo e($project->name); ?></h5>
                    <?php if($project->description): ?>
                        <p class="text-muted small mb-2"><?php echo e(Str::limit($project->description, 80)); ?></p>
                    <?php endif; ?>
                    <span class="badge bg-secondary">Операций: <?php echo e($project->balance_transactions_count); ?></span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-4">Нет проектов с операциями.</div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/projects/index.blade.php ENDPATH**/ ?>