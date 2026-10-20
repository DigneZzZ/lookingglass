# Looking Glass

[![GitHub](https://img.shields.io/github/license/DigneZzZ/lookingglass)](LICENSE.md)
[![PHP](https://img.shields.io/badge/PHP-8.x-blue)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-ready-blue)](docker-compose.caddy.yml)

Современный сетевой Looking Glass с **Tailwind CSS** (shadcn-стиль) интерфейсом и PHP 8.  
Looking Glass — это сетевая утилита для выполнения диагностических команд с удалённого сервера.

![Screenshot](https://raw.githubusercontent.com/DigneZzZ/lookingglass/main/screenshot.png)

## ✨ Возможности

- 🎨 **Современный UI** — Tailwind CSS с shadcn-компонентами
- 🌙 **Тёмная/светлая тема** — Авто-определение + ручное переключение
- ⚡ **Вывод в реальном времени** — Стриминг результатов через JavaScript
- 🛠 **Сетевые инструменты** — ping, ping6, traceroute, traceroute6, mtr, mtr6, whois, bgp
- 📍 **Мультилокации** — Переключение между несколькими LG серверами
- 🔒 **Безопасность** — CSRF защита, валидация ввода, DNS проверки
- 🐳 **Docker ready** — Caddy + PHP-FPM + iPerf3 с автоматическим SSL
- 📊 **Speedtest** — iPerf3 + скачивание тестовых файлов

## 🚀 Быстрый старт (Docker + Caddy)

```bash
# 1. Клонируйте репозиторий
git clone https://github.com/DigneZzZ/lookingglass.git
cd lookingglass

# 2. Создайте .env файл
cat > .env << 'EOF'
# Домен и SSL
LG_DOMAIN=lg.example.com
LG_EMAIL=admin@example.com

# Локация сервера (флаги определяются автоматически!)
CURRENT_LOCATION=Amsterdam, Netherlands
LG_FACILITY=Amsterdam DC
LG_FACILITY_URL=https://www.peeringdb.com/

# IP адреса для тестов
LG_IPV4=1.2.3.4
LG_IPV6=2001:db8::1

# Мультилокации (флаги определяются автоматически по названию!)
LG_LOCATIONS=Amsterdam, Netherlands|https://lg-nl.example.com,Tallinn, Estonia|https://lg-ee.example.com
EOF

# 3. Запустите
docker compose -f docker-compose.caddy.yml up -d --build
```

✅ Looking Glass будет доступен на `https://lg.example.com` с автоматическим SSL от Let's Encrypt!

## 🏳️ Автоматические флаги стран

Флаги определяются автоматически по названию города или страны в локации. Просто укажите название:

```
CURRENT_LOCATION=Amsterdam, Netherlands    → 🇳🇱
LG_LOCATIONS=Tallinn, Estonia|url,...     → 🇪🇪
LG_FACILITY=Frankfurt DC                   → 🇩🇪
```

Поддерживается 80+ стран и городов. Не нужно добавлять эмодзи вручную!

## 📋 Требования

- **Docker вариант:** Docker & Docker Compose
- **Ручная установка:** Linux + PHP 8.x + Web-сервер (Nginx/Apache)

## 📦 Установка

### Docker + Caddy (Рекомендуется)

Автоматический HTTPS через Let's Encrypt.

```bash
git clone https://github.com/DigneZzZ/lookingglass.git && cd lookingglass
cp .env.example .env  # или создайте вручную (см. выше)
nano .env             # отредактируйте под свои нужды
docker compose -f docker-compose.caddy.yml up -d --build
```

### Docker + Nginx

Оригинальная nginx конфигурация (без авто-SSL).

```bash
git clone https://github.com/DigneZzZ/lookingglass.git && cd lookingglass
nano docker-compose.yml  # настройте environment переменные
docker compose up -d --build
```

### Ручная установка

```bash
# 1. Установите зависимости
dnf install mtr traceroute whois php php-posix -y  # RHEL/CentOS/Fedora
# или
apt install mtr traceroute whois php php-cli -y    # Debian/Ubuntu

# 2. Настройте web-сервер (Nginx/Apache)

# 3. Скопируйте файлы в web root
cp -r * /var/www/html/

# 4. Создайте конфиг
cp config.dist.php config.php
nano config.php

# 5. Создайте симлинк для mtr (если нужно)
ln -s /usr/sbin/mtr /usr/bin/mtr
```

## ⚙️ Переменные окружения

### Передача тестовых файлов

`download.php` проверяет имя файла и лимит сессии, затем передаёт отправку
Nginx через `X-Accel-Redirect`. PHP не отправляет содержимое файла. Обе Docker
конфигурации подключают `/srv/files` к PHP и Nginx через общий том. В варианте
с Caddy сервис `downloads` доступен только во внутренней Docker-сети; не
публикуйте его порт, поскольку он доверяет адресу клиента, переданному Caddy.

Ограничения Nginx: два активных скачивания с одного адреса и 16 на сервер.
Они действуют для всех размеров файлов и сессий; при превышении возвращается
429. `send_timeout 30s` закрывает соединение при простое записи, а не задаёт
общую длительность скачивания. HEAD не расходует лимит сессии. GET расходует
одну попытку, даже если клиент затем прерывает скачивание.

Для ручной установки Nginx перенесите зоны `limit_conn_zone`, upstream
`download_php` и подключение `download-locations.conf` из Docker-конфигурации,
указав адрес своего PHP-FPM. Каталог `/srv/files` должен быть доступен Nginx
для чтения. Параметр FastCGI `LG_DOWNLOAD_OFFLOAD=1` задаётся только для точного
маршрута `/download.php`; не задавайте его глобально или через HTTP-заголовок.
Закройте прямой доступ к файлам и внутреннему маршруту `/_speedtest/`.
Без этой настройки скачивания возвращают 503. При ручной установке с Apache
для этого маршрута требуется Nginx с указанной конфигурацией; прежняя передача
через PHP больше не используется.

Проверка в изолированных Docker-контейнерах: `python3 tests/download_offload.py`.

### Доступ к служебным файлам через Caddy

Caddy возвращает 404 для скрытых файлов и каталогов, включая `.env` и `.git`,
каталогов `docker` и `tests`, файлов `docker-compose*` и `config.php` с их
резервными копиями. Эти пути закрыты до обработки PHP и выдачи статики;
варианты регистра тоже закрыты. Не размещайте публичные ресурсы в скрытых
каталогах. Обычные CSS, изображения, PHP-маршруты и скачивания через Nginx
остаются доступны.

Проверки: `php tests/destination_policy.php`,
`python3 tests/caddy_private_files.py` и `python3 tests/download_offload.py`.
HTTP-проверки используют синтетические настройки и отдельные Docker-контейнеры.

| Переменная | Описание | Пример |
|------------|----------|--------|
| `LG_DOMAIN` | Домен для Caddy SSL | `lg.example.com` |
| `LG_EMAIL` | Email для Let's Encrypt | `admin@example.com` |
| `CURRENT_LOCATION` | Локация сервера | `Amsterdam, Netherlands` |
| `LG_IPV4` | IPv4 адрес сервера | `1.2.3.4` |
| `LG_IPV6` | IPv6 адрес сервера | `2001:db8::1` |
| `LG_FACILITY` | Название дата-центра | `Equinix AM5` |
| `LG_FACILITY_URL` | Ссылка на PeeringDB | `https://peeringdb.com/fac/123` |
| `LG_LOGO` | HTML логотип (светлая тема) | `<h2>My LG</h2>` |
| `LG_LOGO_DARK` | HTML логотип (тёмная тема) | `<h2>My LG</h2>` |
| `LG_LOGO_URL` | URL куда ведёт логотип | `https://example.com` |
| `LG_LOCATIONS` | Список мультилокаций | `Name1\|URL1,Name2\|URL2` |

## 🔧 Доступные инструменты

| Команда | Описание |
|---------|----------|
| `ping` / `ping6` | ICMP echo запросы (IPv4/IPv6) |
| `mtr` / `mtr6` | Комбинация ping и traceroute со статистикой |
| `traceroute` / `traceroute6` | Путь до назначения |
| `whois` | WHOIS lookup для IP, домена или ASN |
| `bgp` | BGP route lookup через bgp.tools |

## 📊 iPerf3 Speedtest

iPerf3 включён в `docker-compose.caddy.yml` с защитой от злоупотреблений:

- `--one-off` — Один тест на соединение, затем перезапуск
- `--idle-timeout 30` — Отключение после 30 сек простоя

**Порты:** 5201 TCP/UDP

## 🎨 Варианты интерфейса

| Файл | Описание |
|------|----------|
| `index.php` | Современный Tailwind/shadcn UI (по умолчанию) |
| `index.bootstrap.php` | Оригинальный Bootstrap 5 UI |

## 🔄 Обновление

```bash
cd lookingglass
git pull
docker compose -f docker-compose.caddy.yml up -d --build
```

## 🐛 Troubleshooting

### Порт 80/443 занят
```bash
# Проверьте какой процесс занимает порт
sudo lsof -i :80
sudo lsof -i :443
```

### Проблемы с mtr
```bash
# Создайте симлинк если mtr не найден
sudo ln -s /usr/sbin/mtr /usr/bin/mtr
```

### Логи Docker
```bash
docker compose -f docker-compose.caddy.yml logs -f
docker logs lg-caddy
docker logs lg-php
```

## 👤 Автор

Разработано [DigneZzZ](https://gig.ovh)  
Форк [Hybula Looking Glass](https://github.com/hybula/lookingglass)

## 📄 Лицензия

[Mozilla Public License 2.0](LICENSE.md)
