# Архитектура домашнего сервера (Proxmox)

## 1) Итоговая схема инфраструктуры (ASCII)

```text
                          Internet
                             |
                      [Router/NAT]
                             |
                    (Public/Port forward)
                             |
                        vm-web-host-01
                  (reverse proxy + apps)
                             |
        +--------------------+--------------------+
        |                                         |
   internal services                         storage zone
        |                                         |
  vm-ai-core-01  <-----(read-only mounts)----->  st-data (NAS/disk)
 (OpenClaw+Docker)             |                 (files/docs/media)
        |                      |
        +-----> external APIs  +-----> backups -> bk-store (backup storage)

                    windows/work zone
                          |
                     vm-win-work-01
                     (1C + RDP)

Proxmox host (pve-hv-01) = гипервизор/управление (не хостит приложения)
```

## 2) Слои и зачем они нужны

- **Proxmox host (pve-hv-01)**: управление VM, сеть, storage, бэкапы на уровне гипервизора.
- **AI-core (vm-ai-core-01)**: “ядро” автоматизаций, OpenClaw, фоновые задачи, интеграции.
- **Web-host (vm-web-host-01)**: reverse proxy, docker compose сервисы, staging/production.
- **Windows VM (vm-win-work-01)**: 1С/Windows-only софт, доступ по RDP.
- **Storage layer (st-data-*)**: общие данные (проекты/документы/медиа), единая структура каталогов.
- **Backup layer (bk-*)**: политика и места хранения бэкапов, сценарии восстановления.

## 3) Зависимости и изоляция

- **Публикуется наружу** только `vm-web-host-01` (через reverse proxy).
- **AI-core не должен иметь доступ ко всему storage**: только к выделенным каталогам, часть — read-only.
- **Windows VM** не должна иметь прямого доступа к секретам и бэкапам; обмен — через общие папки “work/share”.
- **Proxmox host** не используется как “сервер приложений”, чтобы не смешивать управление и прикладной слой.

## 4) Базовые предположения (для плана)

- Одна нода Proxmox: `pve-hv-01`.
- Сети логически разделены (минимально): management / internal / public / work / storage.
- Docker используется внутри Ubuntu VM, а не на хосте Proxmox.

