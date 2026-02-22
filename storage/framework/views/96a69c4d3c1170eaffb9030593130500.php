<?php $__env->startSection('title', 'Пользователи'); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Пользователи системы</h1>
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">Добавить пользователя</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th></th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($u->id); ?></td>
                    <td><?php echo e($u->name); ?></td>
                    <td><?php echo e($u->email); ?></td>
                    <td><span class="badge bg-secondary"><?php echo e($u->role); ?></span></td>
                    <td>
                        <a href="<?php echo e(route('admin.users.edit', $u)); ?>" class="btn btn-sm btn-outline-primary">Изменить</a>
                        <?php if($u->id !== auth()->id()): ?>
                        <form method="post" action="<?php echo e(route('admin.users.destroy', $u)); ?>" class="d-inline" onsubmit="return confirm('Удалить пользователя?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?php echo e($users->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/users/index.blade.php ENDPATH**/ ?>