<?php $__env->startSection('title', 'Вход'); ?>
<?php $__env->startSection('content'); ?>
<div class="row justify-content-center g-4">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body text-center p-5">
                <h4 class="card-title mb-4">Вход в личный кабинет</h4>
                <p class="text-muted mb-4">Сначала войдите — затем вам будут доступны разделы: <strong>Транзакции</strong>, <strong>Канбан-доска</strong>, <strong>Профиль</strong>.</p>
                <p class="text-muted small mb-4">Войдите через Telegram или по паролю ниже.</p>
                <?php if($botUsername): ?>
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                        data-telegram-login="<?php echo e($botUsername); ?>"
                        data-size="large"
                        data-auth-url="<?php echo e(url('/cabinet/auth/telegram')); ?>"
                        data-request-access="write">
                    </script>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Telegram-бот не настроен. Обратитесь к администратору.
                    </div>
                <?php endif; ?>
                <p class="small text-muted mt-4 mb-0">Если ваш аккаунт не привязан к карточке клиента, свяжитесь с нами.</p>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Вход по паролю</h5>
                <p class="text-muted small mb-3">Выберите себя в списке и введите пароль. Если пароль не задан администратором — используйте 123.</p>
                <?php if($errors->has('password')): ?>
                    <div class="alert alert-danger py-2"><?php echo e($errors->first('password')); ?></div>
                <?php endif; ?>
                <?php if($clients->isEmpty()): ?>
                    <div class="alert alert-secondary py-2">Нет активных клиентов. Создайте клиента в админке.</div>
                <?php else: ?>
                <form method="post" action="<?php echo e(route('cabinet.password.login')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3 text-start">
                        <label class="form-label">Клиент</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">— Выберите клиента —</option>
                            <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($c->id); ?>" <?php if(old('client_id') == $c->id): echo 'selected'; endif; ?>><?php echo e($c->first_name); ?> <?php echo e($c->last_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" placeholder="Пароль" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/login.blade.php ENDPATH**/ ?>