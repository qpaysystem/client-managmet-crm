# Implementation plan — домашний сервер на Proxmox (практический)

## 1) Таблица VM и storage (единый реестр)

### VM

| Name | Role | OS | vCPU | RAM | Disk (boot) | Network | Public exposure | Backups | Notes |
|---|---|---|---:|---:|---|---|---|---|---|
| `vm-ai-core-01` | AI-core (OpenClaw + automations) | Ubuntu Server 24.04 | 4 | 8–16 GB | 120 GB (ssd) on `local-lvm` | `vmbr0` (internal) | нет | да (daily) | доступ к `/data` строго по whitelist |
| `vm-web-host-01` | Web hosting (reverse proxy + apps) | Ubuntu Server 24.04 | 2–6 | 4–16 GB | 120 GB (ssd) on `local-lvm` | `vmbr0` (public via NAT) | да (80/443) | да (daily) | staging/prod разделять compose-стеками |
| `vm-win-work-01` | Work/1C (RDP) | Windows | 4–8 | 16–32 GB | 200 GB (ssd) on `local-lvm` | `vmbr0` (work) | RDP (ограничить) | выборочно | не хранить критичные данные/бэкапы внутри |

### Storage

| Storage ID | Type | Назначение | Где/путь | Бэкапится | Notes |
|---|---|---|---|---|---|
| `local` | dir | ISO, templates, backup dumps (Proxmox) | `/var/lib/vz` | частично | ISO уже кладём в `/var/lib/vz/template/iso` |
| `local-lvm` | lvmthin | диски VM (images) | VG `pve` | через VM backup | хранит системные диски VM |
| `st-data-01` | (план) | общие данные `/data/*` | отдельный диск/ZFS/NAS | да | источник истины по проектам/докам |
| `bk-store-01` | (план) | backup storage | отдельный диск/NAS | n/a | целевой для VM backups + data backups |

## 2) Имена VM в едином стиле

- Host: `pve-hv-01` (узел Proxmox)
- VM: `vm-<role>-<nn>`:
  - `vm-ai-core-01`
  - `vm-web-host-01`
  - `vm-win-work-01`

## 3) Mount points (рекомендуемые)

> `/data` — на storage layer (`st-data-01`). В VM монтируем в `/mnt/data/*`, а не прямо `/data`, чтобы легче контролировать права.

### `vm-ai-core-01`

| VM path | Source | Mode | Purpose |
|---|---|---|---|
| `/mnt/data/inbox` | `/data/inbox` | RW | входящие файлы для обработки |
| `/mnt/data/shared` | `/data/shared` | RW | общие артефакты/результаты |
| `/mnt/data/projects-ro` | `/data/projects` | RO | чтение проектов/исходников |
| `/mnt/data/docs-ro` | `/data/docs` | RO | чтение документов |

**Не монтировать**: `/data/backups`, `/data/secrets`, корень `/data`.

### `vm-web-host-01`

| VM path | Source | Mode | Purpose |
|---|---|---|---|
| `/mnt/data/shared` | `/data/shared` | RW/RO | загрузки/общие файлы (по нужде) |
| `/mnt/data/media-ro` | `/data/media` | RO | отдача медиа (если нужно) |

### `vm-win-work-01`

- Получает доступ к файлам через отдельную SMB шару (например `\\st-data-01\work-share`) с ACL.
- Не подключать `backups` и `secrets`.

## 4) Структура Docker-проектов (рекомендуемая)

### `vm-web-host-01`

```text
/opt/stacks/
  reverse-proxy/
    docker-compose.yml
    .env.example
    config/
  prod/
    app-1/
      docker-compose.yml
      .env.example
      data/        (bind mounts only for this app)
    app-2/
  staging/
    app-1/
    app-2/
```

Правила:

- **Один проект = одна папка**.
- `prod/` и `staging/` **раздельно**, разные домены/сабдомены.
- Секреты: локальные `.env` (в `.gitignore`), в git только `.env.example`.

### `vm-ai-core-01`

```text
/opt/stacks/
  openclaw/
    docker-compose.yml
    .env.example
    config/
  automations/
    docker-compose.yml
    scripts/
```

## 5) OpenClaw (строго)

- **Где живёт**: `vm-ai-core-01` → `/opt/stacks/openclaw` (Docker Compose).
- **Доступ к папкам**: только через `/mnt/data/*` (см. раздел mount points).
- **Доступ к API**: по whitelist (фиксируем отдельно при подключении: почта, нужные внешние API, внутренний API web-host при необходимости).
- **Запреты**:
  - нет доступа к Proxmox API/SSH
  - нет доступа к `/var/run/docker.sock` (особенно web-host)
  - нет доступа к backup storage и secret storage
  - не запускать контейнеры privileged

## 6) Windows VM (границы)

- **Для чего**: 1С, Windows-only софт, RDP.
- **Что НЕ хранить**:
  - бэкапы (истина — в backup layer)
  - документы/проекты как единственная копия
  - секреты инфраструктуры/ключи
- **Интеграция**:
  - доступ к ограниченной шаре “work-share”
  - доступ к web-host только как клиент (если нужно), без админских токенов

## 7) Бэкапы (что и как часто)

- **Ежедневно**:
  - VM backups: `vm-ai-core-01`, `vm-web-host-01`
  - data backups: `/data/projects`, `/data/docs`
- **Еженедельно**:
  - полный backup всех VM (включая `vm-win-work-01` при необходимости)
  - проверка restore (минимум 1 VM + 1 каталог данных)
- **В первую очередь восстанавливаем**:
  1) storage `/data` (или его критичные каталоги)
  2) `vm-web-host-01`
  3) `vm-ai-core-01`

## 8) Финальный чеклист запуска (коротко)

- [ ] Proxmox обновлён, enterprise repos отключены, no-subscription включены
- [ ] Определён storage `/data` и создана структура каталогов
- [ ] Создана `vm-web-host-01`, reverse proxy поднят, наружу торчит только 80/443
- [ ] Создана `vm-ai-core-01`, Docker работает, mount points ограничены
- [ ] Создана `vm-win-work-01`, RDP ограничен по IP/VPN
- [ ] Настроен backup storage и jobs; есть тест восстановления

