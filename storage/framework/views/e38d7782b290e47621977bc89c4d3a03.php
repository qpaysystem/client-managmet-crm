<?php $__env->startSection('title', 'Главная'); ?>

<?php $__env->startSection('content'); ?>

<section class="hero-banner-wrap" aria-hidden="true">
    <img src="<?php echo e(asset('images/hero-banner.png')); ?>" alt="" class="hero-banner-img" width="1920" height="640" loading="eager">
</section>


<section id="about" class="py-5">
    <div class="container">
        <h2 class="section-title mb-4">Описание компании</h2>
        <div class="row">
            <div class="col-lg-10">
                <p class="lead text-muted">
                    <strong>Периметр Элитного Капитала</strong> — финансово-строительный холдинг. Мы занимаемся строительством жилья, строительно-монтажными работами, проектированием, продажей жилой и нежилой недвижимости.
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Строительство жилья</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Строительно-монтажные работы</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Проектирование</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i> Продажа жилой и нежилой недвижимости</li>
                </ul>
            </div>
        </div>
    </div>
</section>


<section id="objects" class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title mb-4">Строящиеся объекты</h2>
        <div class="row g-4">
            <?php $__empty_1 = true; $__currentLoopData = $projects ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-object h-100">
                    <a href="<?php echo e(route('landing.project', $project)); ?>" class="text-decoration-none text-dark">
                        <div class="img-wrap" style="height: 220px;">
                            <?php if($project->sitePhotos->isNotEmpty()): ?>
                            <img src="<?php echo e($project->sitePhotos->first()->url); ?>" alt="<?php echo e($project->name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 bg-secondary bg-opacity-25">
                                <i class="bi bi-image text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><a href="<?php echo e(route('landing.project', $project)); ?>" class="text-decoration-none text-dark"><?php echo e($project->name); ?></a></h5>
                        <?php if($project->site_description): ?>
                        <p class="small text-muted mb-2"><?php echo e(Str::limit($project->site_description, 120)); ?></p>
                        <?php endif; ?>
                        <a href="<?php echo e(route('landing.project', $project)); ?>" class="btn btn-sm btn-outline-primary">Подробнее и свободные квартиры</a>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <p class="text-muted text-center py-4">Нет объектов для отображения. В админке в карточке проекта включите «Показывать на главной» и заполните данные во вкладке «Размещение на сайте».</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<section id="contact" class="py-5">
    <div class="container text-center">
        <h2 class="section-title mb-4">Связаться с нами</h2>
        <p class="lead mb-2">По вопросам приобретения недвижимости и сотрудничества:</p>
        <a href="tel:+73832910051" class="phone-link fs-3">+7 (383) 291-00-51</a>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('landing.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/landing/index.blade.php ENDPATH**/ ?>