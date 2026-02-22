<?php $__env->startSection('title', 'Квартира № ' . $apartment->apartment_number); ?>
<?php $__env->startSection('content'); ?>
<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="mb-3">
    <a href="<?php echo e(route('cabinet.projects.show', $project)); ?>" class="btn btn-sm btn-outline-secondary">← К проекту «<?php echo e($project->name); ?>»</a>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Планировка</h6>
                <?php if($apartment->layout_photo_url): ?>
                    <img src="<?php echo e($apartment->layout_photo_url); ?>" class="img-fluid rounded" alt="Планировка">
                    <form method="post" action="<?php echo e(route('cabinet.projects.apartments.layout-photo', [$project, $apartment])); ?>" enctype="multipart/form-data" class="mt-2">
                        <?php echo csrf_field(); ?>
                        <input type="file" name="layout_photo" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-1">Заменить</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo e(route('cabinet.projects.apartments.layout-photo', [$project, $apartment])); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="file" name="layout_photo" accept="image/jpeg,image/png,image/webp" class="form-control" required>
                        <button type="submit" class="btn btn-primary btn-sm mt-2">Загрузить планировку</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title mb-0">Квартира № <?php echo e($apartment->apartment_number); ?></h5>
                    <span class="badge bg-<?php echo e($apartment->status === 'sold' ? 'secondary' : ($apartment->status === 'in_pledge' ? 'warning text-dark' : 'success')); ?> fs-6"><?php echo e($apartment->status_label); ?></span>
                </div>
                <table class="table table-borderless mb-0">
                    <tr>
                        <th style="width: 180px;" class="text-muted">Номер квартиры</th>
                        <td><?php echo e($apartment->apartment_number); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Этаж</th>
                        <td><?php echo e($apartment->floor !== null ? $apartment->floor : '—'); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Жилая площадь</th>
                        <td><?php echo e($apartment->living_area !== null ? $apartment->living_area . ' м²' : '—'); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Количество комнат</th>
                        <td><?php echo e($apartment->rooms_count ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Статус</th>
                        <td><?php echo e($apartment->status_label); ?></td>
                    </tr>
                </table>
                <hr>
                <a href="<?php echo e(route('cabinet.projects.apartments.edit', [$project, $apartment])); ?>" class="btn btn-outline-primary btn-sm">Изменить</a>
                <form method="post" action="<?php echo e(route('cabinet.projects.apartments.destroy', [$project, $apartment])); ?>" class="d-inline" onsubmit="return confirm('Удалить карточку квартиры?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/apartments/show.blade.php ENDPATH**/ ?>