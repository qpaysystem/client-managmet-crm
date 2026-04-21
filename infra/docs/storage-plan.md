# Storage plan — каталоги, mount points, доступы

## 1) Цели

- Единая структура каталогов для документов/медиа/проектов.
- Разделение “данные” vs “сервисы” vs “бэкапы”.
- Контроль доступа: что доступно AI-ядру, что только администратору.

## 2) Рекомендуемые корневые каталоги (storage layer)

> Это логическая схема. Физически может быть ZFS dataset, отдельный диск или NAS.

```text
/data
  /projects
  /docs
  /media
  /inbox
  /shared
  /secrets (НЕ монтировать в контейнеры; НЕ давать AI)
  /backups (AI запрещено; write только backup-слою)
```

## 3) Mount points по VM (предложение)

### `vm-ai-core-01`

- **read-write**:
  - `/mnt/data/inbox`  → `/data/inbox`
  - `/mnt/data/shared` → `/data/shared`
- **read-only** (по умолчанию):
  - `/mnt/data/docs-ro`     → `/data/docs`
  - `/mnt/data/projects-ro` → `/data/projects`
  - `/mnt/data/media-ro`    → `/data/media`
- **запрещено**:
  - `/data/backups`, `/data/secrets` (не монтировать вообще)

### `vm-web-host-01`

- **read-write (опционально)**:
  - `/mnt/data/shared` → `/data/shared` (например, для загрузок/общих файлов)
- **read-only (по необходимости)**:
  - `/mnt/data/media-ro` → `/data/media` (если веб-сервис отдаёт медиа)
- **запрещено**:
  - `/data/backups`, `/data/secrets`

### `vm-win-work-01`

- Доступ к файлам — через отдельную “work share” (SMB) с ограничением.
- Не использовать Windows как основное хранилище.

## 4) Какие папки доступны AI-агенту

- **Можно (rw)**: `/data/inbox`, `/data/shared`
- **Можно (ro)**: `/data/projects`, `/data/docs`, `/data/media` (по задаче)
- **Нельзя**: `/data/backups`, `/data/secrets`, корень storage, системные каталоги

