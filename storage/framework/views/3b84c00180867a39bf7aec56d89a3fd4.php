<?php $__env->startSection('title', $client->full_name); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h4 mb-1"><?php echo e($client->full_name); ?></h1>
        <span class="badge bg-<?php echo e($client->status === 'active' ? 'success' : 'secondary'); ?>"><?php echo e($client->status === 'active' ? 'Активный' : 'Неактивный'); ?></span>
    </div>
    <div>
        <a href="<?php echo e(route('admin.clients.edit', $client)); ?>" class="btn btn-outline-primary">Изменить</a>
        <form method="post" action="<?php echo e(route('admin.clients.destroy', $client)); ?>" class="d-inline" onsubmit="return confirm('Удалить клиента?');">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="btn btn-outline-danger">Удалить</button>
        </form>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <?php if($client->photo_path): ?>
            <img src="<?php echo e(asset('storage/'.$client->photo_path)); ?>" alt="" class="img-fluid rounded mb-3" style="max-height: 300px;">
            <form method="post" action="<?php echo e(route('admin.clients.photo.delete', $client)); ?>" class="d-inline" onsubmit="return confirm('Удалить фото?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить фото</button>
            </form>
        <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 200px;"><span class="text-muted">Нет фото</span></div>
        <?php endif; ?>
        <form method="post" action="<?php echo e(route('admin.clients.photo', $client)); ?>" enctype="multipart/form-data" class="mt-2">
            <?php echo csrf_field(); ?>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
            <button type="submit" class="btn btn-sm btn-primary mt-1">Загрузить</button>
        </form>
    </div>
    <div class="col-md-8">
        <table class="table table-bordered">
            <tr><th style="width:180px">Email</th><td><?php echo e($client->email); ?></td></tr>
            <tr><th>Телефон</th><td><?php echo e($client->phone); ?></td></tr>
            <?php if($client->telegram_id || $client->telegram_username): ?>
            <tr><th>Telegram</th><td>
                <?php if($client->telegram_username): ?>
                    <a href="https://t.me/<?php echo e($client->telegram_username); ?>" target="_blank">{{ $client->telegram_username }}</a>
                    <?php if($client->telegram_id): ?><span class="text-muted">(ID: <?php echo e($client->telegram_id); ?>)</span><?php endif; ?>
                <?php else: ?>
                    ID: <?php echo e($client->telegram_id); ?>

                <?php endif; ?>
            </td></tr>
            <?php endif; ?>
            <tr><th>Дата рождения</th><td><?php echo e($client->birth_date?->format('d.m.Y')); ?></td></tr>
            <tr><th>Дата регистрации</th><td><?php echo e($client->registered_at?->format('d.m.Y')); ?></td></tr>
            <tr><th>Баланс</th><td><strong><?php echo e(number_format($client->balance, 2)); ?> <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></strong></td></tr>
        </table>
        <?php $__currentLoopData = $client->customValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($cv->customField && $cv->value !== null && $cv->value !== ''): ?>
            <p class="mb-1"><strong><?php echo e($cv->customField->label); ?>:</strong> <?php echo e($cv->value); ?></p>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <h5 class="mt-4">Операции с балансом</h5>
        <form method="post" action="<?php echo e(route('admin.clients.balance', $client)); ?>" id="balance-form" class="row g-2 mb-3 align-items-end">
            <?php echo csrf_field(); ?>
            <div class="col-auto">
                <label class="form-label small mb-0">Тип операции</label>
                <select name="operation_type" id="operation_type" class="form-select <?php $__errorArgs = ['operation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <?php $__currentLoopData = \App\Models\BalanceTransaction::operationTypeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(old('operation_type', '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['operation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-auto" id="project_wrap" style="display: <?php echo e(old('operation_type') === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE ? 'block' : 'none'); ?>;">
                <label class="form-label small mb-0">Проект</label>
                <select name="project_id" id="project_id" class="form-select">
                    <option value="">— Выберите проект —</option>
                    <?php $__currentLoopData = $projects ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($proj->id); ?>" <?php if(old('project_id') == $proj->id): echo 'selected'; endif; ?>><?php echo e($proj->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['project_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-auto" id="expense_item_wrap" style="display: <?php echo e(old('operation_type') === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE ? 'block' : 'none'); ?>;">
                <label class="form-label small mb-0">Статья расхода</label>
                <select name="project_expense_item_id" id="project_expense_item_id" class="form-select">
                    <option value="">— Сначала выберите проект —</option>
                </select>
                <?php $__errorArgs = ['project_expense_item_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <script type="application/json" id="projects-expense-items-data"><?php echo json_encode($projects ? $projects->mapWithKeys(function ($p) { return [$p->id => $p->expenseItems->map(function ($e) { return ['id' => $e->id, 'name' => $e->name]; })->values()->all()]; })->all() : [], 512) ?></script>
            <div class="col-auto" id="loan_days_wrap" style="display: <?php echo e(old('operation_type') === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'block' : 'none'); ?>;">
                <label class="form-label small mb-0">Количество дней займа</label>
                <input type="number" name="loan_days" id="loan_days" class="form-control <?php $__errorArgs = ['loan_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('loan_days')); ?>" min="1" max="3650" placeholder="дней">
                <?php $__errorArgs = ['loan_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-auto" id="product_pledge_wrap" style="display: <?php echo e(old('operation_type') === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'block' : 'none'); ?>;">
                <label class="form-label small mb-0">Залог (товар)</label>
                <select name="product_id" class="form-select">
                    <option value="">— не выбран</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($prod->id); ?>" <?php if(old('product_id') == $prod->id): echo 'selected'; endif; ?>><?php echo e($prod->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-auto" id="loan_repayment_date_wrap" style="display: <?php echo e(old('operation_type') === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'block' : 'none'); ?>;">
                <label class="form-label small mb-0">Дата возврата</label>
                <div class="form-control-plaintext py-2" id="loan_repayment_date_display">—</div>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Сумма</label>
                <input type="number" name="amount" step="0.01" min="0.01" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Сумма" value="<?php echo e(old('amount')); ?>" required>
                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Комментарий</label>
                <input type="text" name="comment" class="form-control" placeholder="Комментарий" value="<?php echo e(old('comment')); ?>">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Выполнить</button></div>
        </form>
        <script>
            (function() {
                var op = document.getElementById('operation_type');
                var wrap = document.getElementById('loan_days_wrap');
                var wrapPledge = document.getElementById('product_pledge_wrap');
                var wrapDate = document.getElementById('loan_repayment_date_wrap');
                var inputDays = document.getElementById('loan_days');
                var displayDate = document.getElementById('loan_repayment_date_display');
                function formatDate(d) {
                    var day = ('0' + d.getDate()).slice(-2);
                    var month = ('0' + (d.getMonth() + 1)).slice(-2);
                    var year = d.getFullYear();
                    return day + '.' + month + '.' + year;
                }
                function updateRepaymentDate() {
                    var days = parseInt(inputDays.value, 10);
                    if (!isNaN(days) && days >= 1) {
                        var d = new Date();
                        d.setDate(d.getDate() + days);
                        displayDate.textContent = formatDate(d);
                    } else {
                        displayDate.textContent = '—';
                    }
                }
                var projectWrap = document.getElementById('project_wrap');
                var expenseItemWrap = document.getElementById('expense_item_wrap');
                var projectSelect = document.getElementById('project_id');
                var expenseItemSelect = document.getElementById('project_expense_item_id');
                var projectsDataEl = document.getElementById('projects-expense-items-data');
                var projectsData = projectsDataEl ? JSON.parse(projectsDataEl.textContent || '{}') : {};
                var oldExpenseItemId = <?php echo e(json_encode(old('project_expense_item_id'))); ?>;
                function filterExpenseItems() {
                    if (!expenseItemSelect) return;
                    var projectId = projectSelect && projectSelect.value;
                    var saved = oldExpenseItemId || expenseItemSelect.value;
                    expenseItemSelect.innerHTML = '<option value="">— Выберите статью —</option>';
                    if (projectId && projectsData[projectId]) {
                        projectsData[projectId].forEach(function(item) {
                            var opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = item.name;
                            if (String(item.id) === String(saved)) opt.selected = true;
                            expenseItemSelect.appendChild(opt);
                        });
                    }
                    oldExpenseItemId = null;
                }
                function toggle() {
                    var isLoan = op.value === '<?php echo e(\App\Models\BalanceTransaction::OPERATION_LOAN); ?>';
                    var isProjectExpense = op.value === '<?php echo e(\App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE); ?>';
                    wrap.style.display = isLoan ? 'block' : 'none';
                    if (wrapPledge) wrapPledge.style.display = isLoan ? 'block' : 'none';
                    wrapDate.style.display = isLoan ? 'block' : 'none';
                    if (projectWrap) projectWrap.style.display = isProjectExpense ? 'block' : 'none';
                    if (expenseItemWrap) expenseItemWrap.style.display = isProjectExpense ? 'block' : 'none';
                    projectSelect.required = isProjectExpense;
                    if (expenseItemSelect) expenseItemSelect.required = isProjectExpense;
                    inputDays.required = isLoan;
                    if (isProjectExpense) filterExpenseItems();
                    updateRepaymentDate();
                }
                if (projectSelect) projectSelect.addEventListener('change', function() {
                    filterExpenseItems();
                });
                op.addEventListener('change', toggle);
                inputDays.addEventListener('input', updateRepaymentDate);
                inputDays.addEventListener('change', updateRepaymentDate);
                toggle();
                if (projectSelect && projectSelect.value) filterExpenseItems();
            })();
        </script>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Дата</th><th>Тип операции</th><th>Залог / Проект</th><th>Дней</th><th>Дата возврата</th><th>Сумма</th><th>Баланс после</th><th>Комментарий</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $client->balanceTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                        <td><?php echo e($t->operation_type_label); ?></td>
                        <td>
                            <?php if($t->operation_type === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE): ?>
                                <?php if($t->project): ?><a href="<?php echo e(route('admin.projects.show', $t->project)); ?>"><?php echo e($t->project->name); ?></a><?php if($t->projectExpenseItem): ?> — <?php echo e($t->projectExpenseItem->name); ?><?php endif; ?> <?php else: ?> — <?php endif; ?>
                            <?php else: ?>
                                <?php if($t->product): ?><a href="<?php echo e(route('admin.products.edit', $t->product)); ?>"><?php echo e($t->product->name); ?></a><?php else: ?> — <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($t->loan_days ?? '—'); ?></td>
                        <td><?php echo e($t->loan_due_at?->format('d.m.Y') ?? '—'); ?></td>
                        <td><?php echo e($t->type === 'deposit' ? '+' : '−'); ?><?php echo e(number_format($t->amount, 2)); ?></td>
                        <td><?php echo e(number_format($t->balance_after, 2)); ?></td>
                        <td><?php echo e($t->comment); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-muted">Нет операций</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<a href="<?php echo e(route('admin.clients.index')); ?>" class="btn btn-secondary mt-3">← К списку</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/clients/show.blade.php ENDPATH**/ ?>