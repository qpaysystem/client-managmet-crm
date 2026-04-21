# VM plan — ресурсы, критичность, доступы

## 1) Единый стиль имён

- Узел Proxmox: `pve-hv-01`
- VM: `vm-<role>-<nn>` (пример: `vm-ai-core-01`)
- Storage: `st-<purpose>-<nn>` (пример: `st-data-01`)
- Backup: `bk-<purpose>-<nn>` (пример: `bk-store-01`)

## 2) Таблица VM (рекомендация)

> Значения стартовые. По нагрузке (AI/1C/сайты) будем корректировать.

| VM name | Назначение | OS | CPU | RAM | Диск | Сеть | Критичность | Backup | Internet | Общие папки |
|---|---|---|---:|---:|---:|---|---|---|---|---|
| `vm-ai-core-01` | OpenClaw, автоматизации, интеграции, обработка файлов | Ubuntu Server 24.04 LTS | 4 vCPU | 8–16 GB | 80–200 GB (ssd) | internal + storage | высокая | да | да | да (ограниченно) |
| `vm-web-host-01` | reverse proxy, docker compose, staging/prod сервисы | Ubuntu Server 24.04 LTS | 2–6 vCPU | 4–16 GB | 80–200 GB (ssd) | public + internal | высокая | да | да | да (по необходимости) |
| `vm-win-work-01` | 1C, Windows-only софт, RDP | Windows 10/11 Pro или Windows Server | 4–8 vCPU | 16–32 GB | 150–300 GB (ssd) | work + internal (огр.) | средняя/высокая | выборочно | да | ограниченно |

## 3) Storage (логика распределения)

- Диски ВМ: Proxmox storage `local-lvm` (быстро, просто).
- ISO/шаблоны/бэкапы Proxmox: `local` (`/var/lib/vz`) + отдельный `bk-store-*` (желательно).
- Данные: отдельный слой `st-data-*` (ZFS dataset / отдельный диск / NAS по NFS/SMB).

