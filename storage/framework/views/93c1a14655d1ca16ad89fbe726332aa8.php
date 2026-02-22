<?php $__env->startSection('title', 'Главная'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Добро пожаловать, <?php echo e($client->full_name); ?></h1>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Баланс</h6>
                <h3 class="mb-0"><?php echo e(number_format($client->balance, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="<?php echo e(route('cabinet.transactions')); ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-wallet2 fs-2 text-primary me-3"></i>
                    <div>
                        <h6 class="text-muted mb-0">Транзакции</h6>
                        <span class="small">История операций</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo e(route('cabinet.board')); ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <i class="bi bi-kanban fs-2 text-primary me-3"></i>
                    <div>
                        <h6 class="text-muted mb-0">Канбан-доска</h6>
                        <span class="small">Мои задачи</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/dashboard.blade.php ENDPATH**/ ?>