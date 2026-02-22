<?php $__env->startSection('title', 'Задачи команды'); ?>
<?php $__env->startSection('content'); ?>
<h1 class="h4 mb-4">Задачи команды</h1>
<p class="text-muted small">Перетащите задачу в другой столбец, чтобы изменить статус.</p>

<div class="row g-3 kanban-board" data-csrf="<?php echo e(csrf_token()); ?>">
    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php $label = \App\Models\Task::statusLabels()[$status]; $items = $tasksByStatus[$status] ?? []; ?>
    <div class="col-md-3 kanban-col" data-status="<?php echo e($status); ?>">
        <div class="card h-100 border-0 shadow-sm kanban-col-pastel kanban-pastel-<?php echo e($status); ?>">
            <div class="card-header py-2 border-0">
                <strong><?php echo e($label); ?></strong>
                <span class="badge kanban-col-count"><?php echo e(count($items)); ?></span>
            </div>
            <div class="card-body p-2 kanban-drop-zone" style="min-height: 280px;">
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card mb-2 bg-white border kanban-task" draggable="true" data-task-id="<?php echo e($task->id); ?>">
                    <div class="card-body py-2 px-3">
                        <div class="fw-semibold"><?php echo e(Str::limit($task->title, 50)); ?></div>
                        <?php if($task->client): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-person"></i> <?php echo e($task->client->full_name); ?></div>
                        <?php endif; ?>
                        <?php if($task->description): ?>
                            <div class="text-muted small mt-1"><?php echo e(Str::limit($task->description, 80)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php if(empty($items)): ?>
                    <p class="text-muted small mb-0 kanban-empty-msg">Нет задач</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    var board = document.querySelector('.kanban-board');
    var csrf = board && board.dataset.csrf;
    if (!board) return;

    var draggedCard = null;
    var sourceColumn = null;

    board.querySelectorAll('.kanban-task').forEach(function(el) {
        el.addEventListener('dragstart', onDragStart);
        el.addEventListener('dragend', onDragEnd);
    });
    board.querySelectorAll('.kanban-drop-zone').forEach(function(zone) {
        zone.addEventListener('dragover', onDragOver);
        zone.addEventListener('dragleave', onDragLeave);
        zone.addEventListener('drop', onDrop);
    });

    function onDragStart(e) {
        draggedCard = e.target.closest('.kanban-task');
        sourceColumn = draggedCard && draggedCard.closest('.kanban-col');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedCard ? draggedCard.dataset.taskId : '');
        if (draggedCard) draggedCard.classList.add('kanban-dragging');
        e.dataTransfer.setDragImage(draggedCard, 0, 0);
    }

    function onDragEnd(e) {
        if (draggedCard) draggedCard.classList.remove('kanban-dragging');
        board.querySelectorAll('.kanban-drop-zone').forEach(function(z) { z.classList.remove('kanban-drag-over'); });
        draggedCard = null;
        sourceColumn = null;
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var zone = e.currentTarget;
        zone.classList.add('kanban-drag-over');
    }

    function onDragLeave(e) {
        e.currentTarget.classList.remove('kanban-drag-over');
    }

    function onDrop(e) {
        e.preventDefault();
        var zone = e.currentTarget;
        zone.classList.remove('kanban-drag-over');
        var taskId = e.dataTransfer.getData('text/plain');
        var col = zone.closest('.kanban-col');
        var newStatus = col && col.dataset.status;
        if (!taskId || !newStatus || !draggedCard) return;
        if (sourceColumn && col === sourceColumn) return;

        var url = '<?php echo e(url("/tasks")); ?>/' + taskId + '/status';
        fetch(url, {
            method: 'PATCH',
            body: JSON.stringify({ status: newStatus }),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.ok) {
                zone.querySelector('.kanban-empty-msg') && zone.querySelector('.kanban-empty-msg').remove();
                zone.appendChild(draggedCard);
                if (sourceColumn) {
                    var srcZone = sourceColumn.querySelector('.kanban-drop-zone');
                    if (srcZone && srcZone.querySelectorAll('.kanban-task').length === 0) {
                        var p = document.createElement('p');
                        p.className = 'text-muted small mb-0 kanban-empty-msg';
                        p.textContent = 'Нет задач';
                        srcZone.appendChild(p);
                    }
                }
                updateColumnCounts();
            }
        })
        .catch(function() {
            alert('Не удалось обновить статус. Обновите страницу.');
        });
    }

    function updateColumnCounts() {
        board.querySelectorAll('.kanban-col').forEach(function(col) {
            var count = col.querySelectorAll('.kanban-task').length;
            var badge = col.querySelector('.kanban-col-count');
            if (badge) badge.textContent = count;
        });
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.kanban-col-pastel.kanban-pastel-in_development { background: #e3f2fd; }
.kanban-col-pastel.kanban-pastel-processing { background: #fff3e0; }
.kanban-col-pastel.kanban-pastel-execution { background: #e8f5e9; }
.kanban-col-pastel.kanban-pastel-completed { background: #f3e5f5; }
.kanban-col-pastel .card-header .badge { background: rgba(0,0,0,.12) !important; color: #333; }
.kanban-task { cursor: grab; }
.kanban-task:active { cursor: grabbing; }
.kanban-task.kanban-dragging { opacity: 0.6; }
.kanban-drop-zone.kanban-drag-over { background: rgba(13, 110, 253, 0.12); border-radius: 0.25rem; min-height: 280px; }
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.frontend', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/frontend/tasks/board.blade.php ENDPATH**/ ?>