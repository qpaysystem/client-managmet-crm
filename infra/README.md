# infra — домашний сервер (Proxmox)

Этот каталог — будущий репозиторий “инфраструктура как код” для домашнего/рабочего сервера на Proxmox.

## Дерево проекта

```text
infra/
  docs/
  proxmox/
  ai-core/
  web-host/
  windows-vm/
  storage/
  backup/
  scripts/
```

## Быстрый старт

1. Прочитай `infra/docs/architecture.md` — итоговая схема и зоны.
2. Прочитай `infra/docs/vm-plan.md` — список VM, ресурсы, критичность.
3. Прочитай `infra/docs/deployment-checklist.md` — пошаговый запуск.

## Принципы

- Прикладные сервисы **не ставим** на Proxmox host — только в VM/LXC.
- Секреты **не храним в git**. Используем шаблоны `*.example` и локальные `.env`.

