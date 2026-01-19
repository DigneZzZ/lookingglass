# Looking Glass

Modern network looking glass with **Tailwind CSS** (shadcn-style) UI and PHP 8. A looking glass is a network utility that allows you to execute network diagnostic commands from a remote server.

## Features

- 🎨 **Modern UI** — Tailwind CSS with shadcn-style components
- 🌙 **Dark/Light theme** — Auto-detection + manual toggle
- ⚡ **Real-time output** — Streaming command results via JavaScript
- 🛠 **Network tools** — ping, ping6, traceroute, traceroute6, mtr, mtr6
- 📍 **Multi-location** — Switch between multiple LG servers
- 🔒 **Secure** — CSRF protection, input validation, DNS checks
- 🐳 **Docker ready** — Caddy + PHP-FPM + iPerf3
- 📊 **Speedtest** — iPerf3 + download test files

## Quick Start (Docker + Caddy)

```bash
# 1. Clone the repository
git clone https://github.com/DigneZzZ/lookingglass.git
cd lookingglass

# 2. Create .env file
cat > .env << 'EOF'
CURRENT_LOCATION=Amsterdam, Netherlands
LG_DOMAIN=lg.example.com
LG_EMAIL=admin@example.com
LG_IPV4=1.2.3.4
LG_IPV6=::1
LG_FACILITY=DataCenter Name
LG_FACILITY_URL=https://www.peeringdb.com/
LG_LOCATIONS=Amsterdam|https://lg-nl.example.com,Frankfurt|https://lg-de.example.com
EOF

# 3. Build and start
docker compose -f docker-compose.caddy.yml up -d --build
```

Your Looking Glass will be available at `https://lg.example.com` with automatic SSL!

## Requirements

- Docker & Docker Compose
- Or: Linux + PHP 8.x + Web server (Nginx/Apache)

## Installation

### Docker with Caddy (Recommended)

Uses automatic HTTPS via Let's Encrypt.

1. Clone: `git clone https://github.com/hybula/lookingglass.git && cd lookingglass`
2. Copy and edit `.env` file (see example above)
3. Start: `docker compose -f docker-compose.caddy.yml up -d --build`

### Docker with Nginx

Uses the original nginx configuration (no auto-SSL).

1. Clone: `git clone https://github.com/hybula/lookingglass.git && cd lookingglass`
2. Edit `docker-compose.yml` environment variables
3. Build: `docker compose build`
4. Start: `docker compose up -d`

### Manual Installation

1. Install tools: `dnf install mtr traceroute php php-posix -y`
2. Configure web server (Apache/Nginx)
3. Copy files to web root
4. Rename `config.dist.php` to `config.php` and configure
5. Create symlinks for mtr: `ln -s /usr/sbin/mtr /usr/bin/mtr`

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `CURRENT_LOCATION` | Server location | `Amsterdam, Netherlands` |
| `LG_DOMAIN` | Domain for Caddy SSL | `lg.example.com` |
| `LG_EMAIL` | Email for Let's Encrypt | `admin@example.com` |
| `LG_IPV4` | Server IPv4 address | `1.2.3.4` |
| `LG_IPV6` | Server IPv6 address | `2001:db8::1` |
| `LG_FACILITY` | Datacenter name | `Equinix AM5` |
| `LG_FACILITY_URL` | PeeringDB link | `https://peeringdb.com/fac/123` |
| `LG_LOCATIONS` | Multi-location list | `Name1\|URL1,Name2\|URL2` |

## iPerf3 Speedtest

iPerf3 is included in `docker-compose.caddy.yml` with abuse protection:
- `--one-off` — One test per connection, then restart
- `--idle-timeout 30` — Disconnect after 30s idle

## UI Variants

- `index.php` — Modern Tailwind/shadcn UI (default)
- `index.bootstrap.php` — Original Bootstrap 5 UI

## Credits

Developed by [DigneZzZ](https://gig.ovh).  
Originally based on [Hybula Looking Glass](https://github.com/hybula/lookingglass).

## License

Mozilla Public License 2.0
