<?php $__env->startSection('title', 'Канбан-доска'); ?>
<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<style>
.kanban-col-pastel.in_development { background: #e3f2fd; }
.kanban-col-pastel.processing { background: #fff3e0; }
.kanban-col-pastel.execution { background: #e8f5e9; }
.kanban-col-pastel.completed { background: #f3e5f5; }
.kanban-col-pastel .card-header { border-bottom-color: rgba(0,0,0,.06); }
</style>
<?php $__env->stopPush(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Канбан-доска задач</h1>
    <a href="<?php echo e(route('admin.tasks.index')); ?>" class="btn btn-outline-secondary">Список задач</a>
</div>

<div class="row g-3" id="kanban-board">
    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $label = \App\Models\Task::statusLabels()[$status]; $items = $tasksByStatus[$status] ?? []; ?>
    <div class="col-md-3">
        <div class="card h-100 kanban-col-pastel <?php echo e($status); ?>">
            <div class="card-header py-2">
                <strong><?php echo e($label); ?></strong>
                <span class="badge bg-secondary"><?php echo e(count($items)); ?></span>
            </div>
            <div class="card-body p-2 min-vh-100" style="min-height: 300px;">
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card mb-2 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="fw-semibold small"><?php echo e(Str::limit($task->title, 40)); ?></div>
                        <?php if($task->client): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-person"></i> <?php echo e($task->client->full_name); ?></div>
                        <?php endif; ?>
                        <?php if($task->description): ?>
                            <div class="text-muted small mt-1"><?php echo e(Str::limit($task->description, 60)); ?></div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="<?php echo e(route('admin.tasks.edit', $task)); ?>" class="btn btn-sm btn-outline-primary">Изменить</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if(empty($items)): ?>
                    <p class="text-muted small mb-0">Нет задач</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/admin/tasks/board.blade.php ENDPATH**/ ?>