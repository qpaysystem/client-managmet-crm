<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Личный кабинет'); ?> — <?php echo e(config('app.name')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(session('client_id') ? route('cabinet.dashboard') : route('home')); ?>">Личный кабинет</a>
            <?php if(session('client_id')): ?>
            <div class="navbar-nav ms-auto">
                <form method="post" action="<?php echo e(route('cabinet.logout')); ?>" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-light btn-sm">Выход</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php if(session('client_id')): ?>
    <div class="container">
        <ul class="nav nav-tabs nav-fill border-bottom bg-white mb-0">
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('cabinet.dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('cabinet.dashboard')); ?>"><i class="bi bi-house-door me-1"></i> Главная</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('cabinet.transactions') ? 'active' : ''); ?>" href="<?php echo e(route('cabinet.transactions')); ?>"><i class="bi bi-wallet2 me-1"></i> Транзакции</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('cabinet.board') ? 'active' : ''); ?>" href="<?php echo e(route('cabinet.board')); ?>"><i class="bi bi-kanban me-1"></i> Канбан-доска</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('cabinet.projects.*') ? 'active' : ''); ?>" href="<?php echo e(route('cabinet.projects.index')); ?>"><i class="bi bi-folder2-open me-1"></i> Проекты</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo e(request()->routeIs('cabinet.profile') ? 'active' : ''); ?>" href="<?php echo e(route('cabinet.profile')); ?>"><i class="bi bi-person me-1"></i> Профиль</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
    <main class="container py-4">
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($e); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/layout.blade.php ENDPATH**/ ?>