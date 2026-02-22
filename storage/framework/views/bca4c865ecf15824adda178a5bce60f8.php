<?php $__env->startSection('title', 'Профиль'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Профиль</h1>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="text-muted small">Имя</label>
                <p class="mb-0"><?php echo e($client->first_name); ?> <?php echo e($client->last_name); ?></p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Email</label>
                <p class="mb-0"><?php echo e($client->email); ?></p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Телефон</label>
                <p class="mb-0"><?php echo e($client->phone); ?></p>
            </div>
            <div class="col-md-6">
                <label class="text-muted small">Баланс</label>
                <p class="mb-0"><?php echo e(number_format($client->balance, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></p>
            </div>
            <?php if($client->telegram_username || $client->telegram_id): ?>
            <div class="col-12">
                <label class="text-muted small">Telegram</label>
                <p class="mb-0">
                    <?php if($client->telegram_username): ?>
                        <a href="https://t.me/<?php echo e($client->telegram_username); ?>" target="_blank" rel="noopener">{{ $client->telegram_username }}</a>
                    <?php endif; ?>
                    <?php if($client->telegram_id): ?>
                        <span class="text-muted small">(ID: <?php echo e($client->telegram_id); ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h5 class="h6 mb-3">Сменить пароль входа в личный кабинет</h5>
        <?php if($errors->has('current_password') || $errors->has('password')): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php if($errors->has('current_password')): ?><div><?php echo e($errors->first('current_password')); ?></div><?php endif; ?>
                <?php if($errors->has('password')): ?><div><?php echo e($errors->first('password')); ?></div><?php endif; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo e(route('cabinet.profile.password')); ?>" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-12">
                <label class="form-label">Текущий пароль</label>
                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="col-12">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <div class="col-12">
                <label class="form-label">Повторите новый пароль</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="6" autocomplete="new-password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Сохранить новый пароль</button>
            </div>
        </form>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">Для изменения имени, контактов и других данных обратитесь к администратору.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/profile.blade.php ENDPATH**/ ?>