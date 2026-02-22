<?php $__env->startSection('title', $project->name); ?>
<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <a href="<?php echo e(route('cabinet.projects.index')); ?>" class="btn btn-sm btn-outline-secondary mb-2">← К списку проектов</a>
        <h1 class="h4 mb-1"><?php echo e($project->name); ?></h1>
        <?php if($project->description): ?>
            <p class="text-muted small mb-0"><?php echo e(Str::limit($project->description, 300)); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" id="projectTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab">Расходы на проект</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="apartments-tab" data-bs-toggle="tab" data-bs-target="#apartments" type="button" role="tab">Квартиры</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button" role="tab">Рабочая документация</button>
    </li>
</ul>

<div class="tab-content" id="projectTabsContent">
    
    <div class="tab-pane fade show active" id="expenses" role="tabpanel">
        <h5 class="mb-3">Расходы по статьям</h5>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Статья расхода</th>
                            <th class="text-end">Кол-во операций</th>
                            <th class="text-end">Сумма, <?php echo e(\App\Models\Setting::get('currency', 'RUB')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $summaryByItem; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($row['item']->name); ?></td>
                            <td class="text-end"><?php echo e($row['count']); ?></td>
                            <td class="text-end"><?php echo e(number_format($row['total'], 2)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="text-muted">Нет расходов по статьям</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if($summaryByItem->isNotEmpty()): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th>Итого</th>
                            <th class="text-end"><?php echo e($project->balanceTransactions->count()); ?></th>
                            <th class="text-end"><?php echo e(number_format($grandTotal, 2)); ?></th>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <h5 class="mb-3">Сводка по клиентам</h5>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Клиент</th>
                            <th class="text-end">Операций</th>
                            <th class="text-end">Сумма расходов</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $summaryByClient; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($row['client']->full_name); ?></td>
                            <td class="text-end"><?php echo e($row['count']); ?></td>
                            <td class="text-end"><?php echo e(number_format($row['total'], 2)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="text-muted">Нет данных по клиентам</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h5 class="mb-3">Все транзакции по проекту</h5>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Статья расхода</th>
                                <th class="text-end">Сумма</th>
                                <th>Комментарий</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $project->balanceTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($t->created_at->format('d.m.Y H:i')); ?></td>
                                <td><?php echo e($t->client->full_name); ?></td>
                                <td><?php echo e($t->projectExpenseItem?->name ?? '—'); ?></td>
                                <td class="text-end"><?php echo e(number_format($t->amount, 2)); ?></td>
                                <td class="text-muted small"><?php echo e(Str::limit($t->comment, 50)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5" class="text-muted">Нет операций по проекту</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="tab-pane fade" id="apartments" role="tabpanel">
        <?php
            $apts = $project->apartments;
            $countSold = $apts->where('status', 'sold')->count();
            $countAvailable = $apts->where('status', 'available')->count();
            $countInPledge = $apts->where('status', 'in_pledge')->count();
            $areaSold = $apts->where('status', 'sold')->sum(fn($a) => (float) $a->living_area);
            $areaTotal = $apts->sum(fn($a) => (float) $a->living_area);
            $areaLeft = $areaTotal - $areaSold;
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="card-title mb-3">Сводка по квартирам</h6>
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Продано</div>
                        <strong><?php echo e($countSold); ?> кв.</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Свободно</div>
                        <strong><?php echo e($countAvailable); ?> кв.</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">В залоге</div>
                        <strong><?php echo e($countInPledge); ?> кв.</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">м² продано</div>
                        <strong><?php echo e(number_format($areaSold, 1)); ?> м²</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">м² осталось</div>
                        <strong><?php echo e(number_format($areaLeft, 1)); ?> м²</strong>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="text-muted small">Всего м²</div>
                        <strong><?php echo e(number_format($areaTotal, 1)); ?> м²</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-3">
            <a href="<?php echo e(route('cabinet.projects.apartments.create', $project)); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Создать карточку квартиры</a>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <label class="form-label small mb-0 text-nowrap">Статус:</label>
                <select id="apartment-filter" class="form-select form-select-sm" style="width: auto;">
                    <option value="">Все</option>
                    <option value="available">Свободно</option>
                    <option value="in_pledge">В залоге</option>
                    <option value="sold">Продано</option>
                </select>
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="apartment-view" id="view-cards" value="cards" checked>
                    <label class="btn btn-outline-secondary" for="view-cards" title="Панельки"><i class="bi bi-grid-3x2-gap"></i></label>
                    <input type="radio" class="btn-check" name="apartment-view" id="view-list" value="list">
                    <label class="btn btn-outline-secondary" for="view-list" title="Список"><i class="bi bi-list-ul"></i></label>
                </div>
            </div>
        </div>

        
        <div id="apartment-cards-wrap" class="apartment-view-wrap">
            <div class="row g-3" id="apartment-cards">
                <?php $__empty_1 = true; $__currentLoopData = $project->apartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $apt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="col-md-6 col-lg-4 apartment-item" data-status="<?php echo e($apt->status); ?>" data-number="<?php echo e($apt->apartment_number); ?>" data-number-int="<?php echo e(is_numeric($apt->apartment_number) ? (int)$apt->apartment_number : 999999); ?>" data-floor="<?php echo e($apt->floor ?? ''); ?>" data-area="<?php echo e($apt->living_area ?? ''); ?>" data-rooms="<?php echo e($apt->rooms_count ?? ''); ?>">
                    <div class="card border-0 shadow-sm h-100">
                        <?php if($apt->layout_photo_url): ?>
                            <img src="<?php echo e($apt->layout_photo_url); ?>" class="card-img-top" alt="Планировка" style="height: 140px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center card-img-top" style="height: 140px;"><i class="bi bi-image text-muted fs-1"></i></div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title mb-1">Квартира № <?php echo e($apt->apartment_number); ?></h6>
                            <span class="badge bg-<?php echo e($apt->status === 'sold' ? 'secondary' : ($apt->status === 'in_pledge' ? 'warning text-dark' : 'success')); ?>"><?php echo e($apt->status_label); ?></span>
                            <p class="small text-muted mb-0 mt-1">
                                <?php if($apt->floor !== null): ?> Этаж <?php echo e($apt->floor); ?> · <?php endif; ?>
                                <?php if($apt->rooms_count): ?> <?php echo e($apt->rooms_count); ?> комн. · <?php endif; ?>
                                <?php if($apt->living_area): ?> <?php echo e($apt->living_area); ?> м² <?php endif; ?>
                            </p>
                            <?php if($apt->owner_data): ?>
                            <p class="small mb-0 mt-1"><span class="text-muted">Владелец:</span> <?php echo e(Str::limit(strip_tags($apt->owner_data), 35)); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo e(route('cabinet.projects.apartments.show', [$project, $apt])); ?>" class="btn btn-sm btn-outline-primary mt-2">Карточка</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12 apartment-empty">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-4">Нет квартир. Создайте карточку квартиры.</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div id="apartment-list-wrap" class="apartment-view-wrap d-none">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="cabinet-apartments-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="apartment-sort" data-sort="number" style="cursor:pointer" title="Сортировать">№ <i class="bi bi-arrow-down-up small"></i></th>
                                    <th class="apartment-sort" data-sort="status" style="cursor:pointer" title="Сортировать">Статус <i class="bi bi-arrow-down-up small"></i></th>
                                    <th class="apartment-sort" data-sort="floor" style="cursor:pointer" title="Сортировать">Этаж <i class="bi bi-arrow-down-up small"></i></th>
                                    <th class="apartment-sort" data-sort="rooms" style="cursor:pointer" title="Сортировать">Комнат <i class="bi bi-arrow-down-up small"></i></th>
                                    <th class="apartment-sort" data-sort="area" style="cursor:pointer" title="Сортировать">Площадь, м² <i class="bi bi-arrow-down-up small"></i></th>
                                    <th>Владелец</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody id="apartment-list">
                                <?php $__empty_1 = true; $__currentLoopData = $project->apartments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $apt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="apartment-item" data-status="<?php echo e($apt->status); ?>" data-number="<?php echo e($apt->apartment_number); ?>" data-number-int="<?php echo e(is_numeric($apt->apartment_number) ? (int)$apt->apartment_number : 999999); ?>" data-floor="<?php echo e($apt->floor ?? ''); ?>" data-area="<?php echo e($apt->living_area ?? ''); ?>" data-rooms="<?php echo e($apt->rooms_count ?? ''); ?>">
                                    <td><?php echo e($apt->apartment_number); ?></td>
                                    <td><span class="badge bg-<?php echo e($apt->status === 'sold' ? 'secondary' : ($apt->status === 'in_pledge' ? 'warning text-dark' : 'success')); ?>"><?php echo e($apt->status_label); ?></span></td>
                                    <td><?php echo e($apt->floor !== null ? $apt->floor : '—'); ?></td>
                                    <td><?php echo e($apt->rooms_count ?: '—'); ?></td>
                                    <td><?php echo e($apt->living_area ? number_format((float)$apt->living_area, 1) : '—'); ?></td>
                                    <td class="small text-muted"><?php echo e($apt->owner_data ? Str::limit(strip_tags($apt->owner_data), 40) : '—'); ?></td>
                                    <td><a href="<?php echo e(route('cabinet.projects.apartments.show', [$project, $apt])); ?>" class="btn btn-sm btn-outline-primary">Карточка</a></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr class="apartment-empty"><td colspan="7" class="text-muted text-center py-4">Нет квартир. Создайте карточку квартиры.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-2 d-none" id="apartment-filter-empty">Нет квартир по выбранному фильтру.</p>
    </div>

    <script>
    (function() {
        var filterEl = document.getElementById('apartment-filter');
        var viewCardsWrap = document.getElementById('apartment-cards-wrap');
        var viewListWrap = document.getElementById('apartment-list-wrap');
        var filterEmptyEl = document.getElementById('apartment-filter-empty');
        var viewKey = 'cabinet_apartment_view';
        var filterKey = 'cabinet_apartment_filter';

        function getView() { return document.querySelector('input[name="apartment-view"]:checked')?.value || 'cards'; }
        function getFilter() { return (filterEl && filterEl.value) || ''; }

        function applyFilter() {
            var status = getFilter();
            var cards = document.querySelectorAll('#apartment-cards .apartment-item');
            var rows = document.querySelectorAll('#apartment-list .apartment-item');
            var emptyCards = document.querySelector('#apartment-cards .apartment-empty');
            var emptyRows = document.querySelector('#apartment-list .apartment-empty');
            var visibleCount = 0;
            cards.forEach(function(el) {
                var show = !status || el.getAttribute('data-status') === status;
                el.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            rows.forEach(function(el) {
                var show = !status || el.getAttribute('data-status') === status;
                el.style.display = show ? '' : 'none';
            });
            if (emptyCards) emptyCards.style.display = visibleCount ? 'none' : '';
            if (emptyRows) emptyRows.style.display = visibleCount ? 'none' : '';
            if (filterEmptyEl) filterEmptyEl.classList.toggle('d-none', !status || visibleCount > 0);
            try { localStorage.setItem(filterKey, status); } catch (e) {}
        }

        function applyView() {
            var view = getView();
            if (view === 'list') {
                viewCardsWrap.classList.add('d-none');
                viewListWrap.classList.remove('d-none');
            } else {
                viewCardsWrap.classList.remove('d-none');
                viewListWrap.classList.add('d-none');
            }
            try { localStorage.setItem(viewKey, view); } catch (e) {}
        }

        var sortState = { key: 'number', dir: 1 };
        var statusOrder = { available: 1, in_pledge: 2, sold: 3 };
        function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
        function getSortKey(el, key) {
            if (key === 'number') return parseInt(el.getAttribute('data-number-int'), 10) || num(el.getAttribute('data-number')) || 0;
            if (key === 'floor') return num(el.getAttribute('data-floor'));
            if (key === 'area') return num(el.getAttribute('data-area'));
            if (key === 'rooms') return num(el.getAttribute('data-rooms'));
            if (key === 'status') return statusOrder[el.getAttribute('data-status')] || 0;
            return 0;
        }
        function applySort(key, dir) {
            function doSort(parent, selector) {
                var items = [].slice.call(parent.querySelectorAll(selector));
                if (!items.length) return;
                items.sort(function(a, b) {
                    var va = getSortKey(a, key), vb = getSortKey(b, key);
                    if (key === 'number' && va === vb) {
                        var na = (a.getAttribute('data-number') || '').toString(), nb = (b.getAttribute('data-number') || '').toString();
                        return dir * (na.localeCompare(nb, undefined, { numeric: true }) || 0);
                    }
                    if (va < vb) return -dir;
                    if (va > vb) return dir;
                    return 0;
                });
                items.forEach(function(r) { parent.appendChild(r); });
            }
            doSort(document.getElementById('apartment-cards'), '.apartment-item');
            doSort(document.getElementById('apartment-list'), '.apartment-item');
        }
        var cabinetTable = document.getElementById('cabinet-apartments-table');
        if (cabinetTable) {
            cabinetTable.querySelectorAll('.apartment-sort').forEach(function(th) {
                th.addEventListener('click', function() {
                    var key = this.getAttribute('data-sort');
                    if (!key) return;
                    sortState.dir = (sortState.key === key ? -sortState.dir : 1);
                    sortState.key = key;
                    applySort(key, sortState.dir);
                });
            });
        }

        if (filterEl) filterEl.addEventListener('change', applyFilter);
        document.querySelectorAll('input[name="apartment-view"]').forEach(function(radio) {
            radio.addEventListener('change', applyView);
        });

        try {
            var savedView = localStorage.getItem(viewKey);
            if (savedView === 'list') document.getElementById('view-list').checked = true;
            var savedFilter = localStorage.getItem(filterKey);
            if (savedFilter && filterEl) filterEl.value = savedFilter;
        } catch (e) {}
        applyView();
        applyFilter();
    })();
    </script>

    
    <div class="tab-pane fade" id="docs" role="tabpanel">
        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#add-document-form" aria-expanded="false"><i class="bi bi-plus-lg me-1"></i> Добавить документ</button>
            <div class="collapse mt-3" id="add-document-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Новый документ</h6>
                        <form method="post" action="<?php echo e(route('cabinet.projects.documents.store', $project)); ?>" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small mb-0">Название</label>
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="Например: Договор" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-0">Файл (необязательно)</label>
                                    <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.zip">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm">Добавить</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <?php $__empty_1 = true; $__currentLoopData = $project->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-1"><?php echo e($doc->name); ?></h6>
                        <p class="small text-muted mb-2">Добавлен: <?php echo e($doc->created_at->format('d.m.Y H:i')); ?></p>
                        <?php if($doc->file_url): ?>
                        <p class="small mb-2">
                            <a href="<?php echo e($doc->file_url); ?>" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-file-earmark-arrow-down me-1"></i><?php echo e($doc->file_name); ?></a>
                        </p>
                        <?php else: ?>
                        <p class="small text-muted mb-2">Файл не прикреплён</p>
                        <?php endif; ?>
                        <div class="d-flex gap-1 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-doc-<?php echo e($doc->id); ?>" aria-expanded="false">Изменить</button>
                            <form method="post" action="<?php echo e(route('cabinet.projects.documents.destroy', [$project, $doc])); ?>" class="d-inline" onsubmit="return confirm('Удалить документ?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </div>
                        <div class="collapse mt-2" id="edit-doc-<?php echo e($doc->id); ?>">
                            <form method="post" action="<?php echo e(route('cabinet.projects.documents.update', [$project, $doc])); ?>" enctype="multipart/form-data">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PUT'); ?>
                                <div class="mb-2">
                                    <input type="text" name="name" class="form-control form-control-sm" value="<?php echo e(old('name', $doc->name)); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.zip">
                                    <small class="text-muted">Оставьте пустым, чтобы не менять файл</small>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-4">Нет документов. Нажмите «Добавить документ».</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <hr class="my-4">
        <p class="text-muted small mb-2">Дополнительные поля (название — значение)</p>
        <form method="post" action="<?php echo e(route('cabinet.projects.document-fields.store', $project)); ?>" class="row g-2 align-items-end mb-2">
            <?php echo csrf_field(); ?>
            <div class="col-auto">
                <input type="text" name="name" class="form-control form-control-sm" placeholder="Название поля" required>
            </div>
            <div class="col-auto">
                <input type="text" name="value" class="form-control form-control-sm" placeholder="Значение">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Добавить поле</button>
            </div>
        </form>
        <?php if($project->documentFields->isNotEmpty()): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <?php $__currentLoopData = $project->documentFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($field->name); ?></td>
                        <td><?php echo e(Str::limit($field->value, 200)); ?></td>
                        <td class="text-end" style="width: 80px;">
                            <form method="post" action="<?php echo e(route('cabinet.projects.document-fields.destroy', [$project, $field])); ?>" class="d-inline" onsubmit="return confirm('Удалить поле?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('cabinet.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/evgeny/client-management-crm/resources/views/cabinet/projects/show.blade.php ENDPATH**/ ?>