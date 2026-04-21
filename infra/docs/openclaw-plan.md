# OpenClaw plan — размещение, доступы, запреты

## 1) Где живёт OpenClaw

- VM: **`vm-ai-core-01`**
- Runtime: **Docker / docker compose**
- Код/конфиги в git: `infra/ai-core/openclaw/` (без секретов)

## 2) Доступ к папкам (минимально необходимый)

### Разрешено

- **RW**:
  - `/mnt/data/inbox`  (входящие файлы для обработки)
  - `/mnt/data/shared` (результаты/общие артефакты)
- **RO** (по задаче):
  - `/mnt/data/projects-ro`
  - `/mnt/data/docs-ro`
  - `/mnt/data/media-ro`

### Запрещено

- Любые mount points на:
  - `/data/backups`
  - `/data/secrets`
- Доступ к `/var/lib/vz`, `/etc/pve`, storage Proxmox и т.п.

## 3) Доступ к API/сервисам

### Разрешено (примерный класс)

- Почта (SMTP/IMAP) для отправки/получения задач (через отдельного бота/аккаунт)
- Внешние API по whitelist (в файле `infra/docs/openclaw-plan.md` фиксируется список)
- Доступ к внутренним сервисам (например, web-host API) **по отдельным токенам**

### Запрещено

- Proxmox API/SSH (если нет отдельной задачи и отдельного service-account)
- Доступ к Docker socket на web-host/prod
- Любые “admin credentials” от инфраструктуры

## 4) Права, которые запрещены на уровне Docker/OS

- `--privileged`
- монтирование `/var/run/docker.sock`
- `CAP_SYS_ADMIN` и широкие capabilities без необходимости
- доступ к сетевым сегментам management/storage напрямую (если можно через шлюз)

