<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Клиенты'); ?> — <?php echo e(config('app.name')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>body { background: #f8f9fa; } .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); }</style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo e(route('frontend.clients.index')); ?>"><?php echo e(config('app.name')); ?></a>
            <div class="navbar-nav">
                <a class="nav-link" href="<?php echo e(route('frontend.clients.index')); ?>">Клиенты</a>
                <a class="nav-link" href="<?php echo e(route('frontend.products.index')); ?>">Товары</a>
                <a class="nav-link" href="<?php echo e(route('frontend.tasks.board')); ?>">Задачи команды</a>
                <a class="nav-link" href="<?php echo e(route('cabinet.login')); ?>">Личный кабинет</a>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/evgeny/client-management-crm/resources/views/layouts/frontend.blade.php ENDPATH**/ ?>