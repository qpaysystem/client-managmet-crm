<?php $__env->startSection('title', 'Настройки'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Системные настройки</h1>
<form method="post" action="<?php echo e(route('admin.settings.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Общие</h5>
            <div class="mb-3">
                <label class="form-label">Валюта для баланса</label>
                <input type="text" name="currency" class="form-control" value="<?php echo e($settings['currency']); ?>" maxlength="10" placeholder="RUB, USD, EUR">
            </div>
            <div class="mb-3">
                <label class="form-label">Максимальный размер загружаемого файла (МБ)</label>
                <input type="number" name="max_upload_mb" class="form-control" value="<?php echo e($settings['max_upload_mb']); ?>" min="1" max="50">
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="mail_notifications" value="1" class="form-check-input" id="mail_notifications" <?php echo e(($settings['mail_notifications'] ?? '0') == '1' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="mail_notifications">Включить почтовые уведомления</label>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Telegram-бот</h5>
            <p class="text-muted small">Уведомления о проведении транзакций. Создайте бота через <a href="https://t.me/BotFather" target="_blank">@BotFather</a>, получите токен и chat_id.</p>
            <div class="mb-3">
                <label class="form-label">Токен бота</label>
                <input type="text" name="telegram_bot_token" class="form-control" value="<?php echo e($settings['telegram_bot_token'] ?? ''); ?>" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
            </div>
            <div class="mb-3">
                <label class="form-label">Chat ID</label>
                <input type="text" name="telegram_chat_id" class="form-control" value="<?php echo e($settings['telegram_chat_id'] ?? ''); ?>" placeholder="123456789 или -1001234567890">
            </div>
            <div class="form-check">
                <input type="checkbox" name="telegram_notify_transactions" value="1" class="form-check-input" id="telegram_notify" <?php echo e(($settings['telegram_notify_transactions'] ?? '0') == '1' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="telegram_notify">Отправлять уведомления о транзакциях</label>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/settings/index.blade.php ENDPATH**/ ?>