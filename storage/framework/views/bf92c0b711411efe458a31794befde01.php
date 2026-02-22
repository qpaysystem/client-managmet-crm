<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Админ'); ?> — <?php echo e(config('app.name')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="d-flex">
    <nav class="navbar navbar-dark bg-dark flex-column align-items-stretch p-3" style="width: 220px; min-height: 100vh;">
        <a class="navbar-brand mb-4" href="<?php echo e(route('admin.dashboard')); ?>">CRM</a>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.dashboard')); ?>"><i class="bi bi-grid"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.clients.index')); ?>"><i class="bi bi-people"></i> Клиенты</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.transactions.index')); ?>"><i class="bi bi-journal-text"></i> Транзакции</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.tasks.index')); ?>"><i class="bi bi-check2-square"></i> Задачи</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.products.index')); ?>"><i class="bi bi-box-seam"></i> ТМЦ</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.projects.index')); ?>"><i class="bi bi-folder2-open"></i> Проекты</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.custom-fields.index')); ?>"><i class="bi bi-list-ul"></i> Поля клиентов</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.users.index')); ?>"><i class="bi bi-person-gear"></i> Пользователи</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.activity')); ?>"><i class="bi bi-activity"></i> Активность</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?php echo e(route('admin.settings.index')); ?>"><i class="bi bi-gear"></i> Настройки</a></li>
        </ul>
        <hr class="text-secondary">
        <form method="post" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-right"></i> Выход</button>
        </form>
    </nav>
    <main class="flex-grow-1 p-4">
        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/evgeny/client-management-crm/resources/views/layouts/admin.blade.php ENDPATH**/ ?>