# Deployment checklist — запуск по шагам

## 0) Подготовка (инвентаризация)

- [ ] IP/сеть/доступ к Proxmox (`https://<pve>:8006`)
- [ ] Учётные данные администратора Proxmox (root/pam)
- [ ] Доменные имена (если планируется внешний доступ)
- [ ] Место под storage и backups (диск/NAS/внешний диск)

## 1) Proxmox base

- [ ] Обновить систему (no-subscription repos, `apt full-upgrade`)
- [ ] Настроить время/часовой пояс
- [ ] Создать пользователя-админа (не только root)
- [ ] Настроить firewall базово (минимум: не публиковать лишнее)

## 2) Storage layer

- [ ] Определить, где живёт `/data` (ZFS/диск/NAS)
- [ ] Создать структуру каталогов `/data/*`
- [ ] Настроить права/ACL на шары (SMB/NFS — если используются)

## 3) VM: AI-core

- [ ] Создать `vm-ai-core-01` (Ubuntu 24.04)
- [ ] Установить Docker + docker compose
- [ ] Подмонтировать выделенные каталоги `/mnt/data/*`
- [ ] Поднять OpenClaw (compose) без секретов в git

## 4) VM: Web-host

- [ ] Создать `vm-web-host-01` (Ubuntu 24.04)
- [ ] Поднять reverse proxy (Traefik/Nginx) + базовый health endpoint
- [ ] Развести staging/prod docker compose
- [ ] Проверить, что наружу смотрит только reverse proxy

## 5) VM: Windows

- [ ] Создать `vm-win-work-01` (RDP доступ)
- [ ] Установить 1С и нужный Windows-only софт
- [ ] Подключить рабочую шару (ограниченная)

## 6) Backups

- [ ] Настроить Proxmox backup jobs (VM backups)
- [ ] Настроить data backup `/data/projects` и `/data/docs`
- [ ] Проверить restore: восстановить тестовую VM/файл

## Критерий “запуск завершён”

- [ ] Есть рабочие VM (ai-core/web-host/windows) + доступы
- [ ] Storage смонтирован и права корректны
- [ ] Бэкапы выполняются по расписанию и есть успешная проверка восстановления

