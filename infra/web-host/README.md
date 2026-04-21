# web-host

Здесь живут docker/compose стеки и конфиги для `vm-web-host-01`:

- reverse proxy
- staging/production проекты
- базы данных при необходимости (предпочтительно изолировать по проектам)

## Можно в git

- `docker-compose.yml`
- `*.env.example`
- конфиги reverse proxy (без приватных ключей)

## Нельзя в git

- TLS private keys, реальные токены, пароли БД
- kubeconfig/VPN/ssh ключи

