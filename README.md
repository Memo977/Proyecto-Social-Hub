# SocialHub

**Social Hub** es una aplicación web en **Laravel** para **gestionar y programar publicaciones** en múltiples redes sociales.  
Incluye integración con **Mastodon** y **Reddit**, autenticación **2FA (TOTP)**, horarios de publicación y **colas** para ejecución en background.

<p align="left">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/Node.js-20+-339933?logo=node.js&logoColor=white" />
  <img src="https://img.shields.io/badge/Vite-7+-646CFF?logo=vite&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8+-4479A1?logo=mysql&logoColor=white" />
</p>

---

## Características principales

- 🔑 **Breeze + 2FA (TOTP)** para seguridad  
- 🌐 **OAuth** con:
  - Mastodon (instancia configurable)
  - Reddit (apps tipo *script*)
- 🗓️ **Horarios** configurables por usuario  
- 📤 Modos de publicación: **now**, **scheduled**, **queue**  
- ⚙️ **Workers** con Laravel Queue (driver `database`)  
- 📊 Vistas para **pendientes** e **historial**  

---

## 🌐 Integraciones OAuth

| Proveedor | Logo | Estado |
|-----------|------|--------|
| Mastodon  | <img src="https://img.shields.io/badge/Mastodon-6364FF?logo=mastodon&logoColor=white&style=for-the-badge" width="120"/> | ✅ Implementado |
| Reddit    | <img src="https://img.shields.io/badge/Reddit-FF4500?logo=reddit&logoColor=white&style=for-the-badge" width="120"/> | ✅ Implementado |

---

## ⚡ Instalación rápida

> Requisitos: **PHP 8.2+**, **Composer**, **Node 20+**, **MySQL/MariaDB**.  
> Redis es opcional (la cola usa `database` por defecto).

```bash
# 1) Clonar proyecto
git clone <repo-url>
cd social-hub

# 2) Instalar dependencias
composer install
npm install

# 3) Configurar entorno
cp .env.example .env
php artisan key:generate

# 4) Migrar y seedear
php artisan migrate --seed
```

---

## Desarrollo local

Ejecuta en paralelo:

```bash
php artisan serve        # Servidor PHP (http://localhost:8000)
npm run dev              # Frontend (Vite)
php artisan queue:work   # Worker de colas
```

> **Nota**: adapta los comandos a tu plataforma (local, VM, Docker, servidor real, WSL, etc.).

---

## Variables de entorno (ejemplo)

| Clave                      | Ejemplo / Nota |
|---------------------------|----------------|
| `APP_NAME`                | `SocialHub` |
| `APP_URL`                 | `http://localhost:8000` |
| `DB_CONNECTION`           | `mysql` |
| `DB_HOST` / `DB_PORT`     | `127.0.0.1` / `3306` |
| `DB_DATABASE`             | `social_hub` |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / *(según tu entorno)* |
| `QUEUE_CONNECTION`        | `database` |
| `CACHE_STORE`             | `database` |
| `MAIL_*`                  | SMTP de tu preferencia (Mailtrap recomendado) |
| `MASTODON_DOMAIN`         | `https://mastodon.social` (o tu instancia) |
| `MASTODON_ID` / `MASTODON_SECRET` | *(credenciales OAuth)* |
| `MASTODON_REDIRECT`       | `${APP_URL}/auth/mastodon/callback` |
| `REDDIT_CLIENT_ID` / `REDDIT_CLIENT_SECRET` | *(credenciales OAuth)* |
| `REDDIT_REDIRECT_URI`     | `${APP_URL}/oauth/reddit/callback` |
| `REDDIT_USER_AGENT`       | `SocialHub/1.0 (by u/TU_USUARIO)` *(obligatorio en Reddit)* |

> Crea tus apps en **Mastodon** y **Reddit**, y define los **redirect URIs** exactamente como arriba.

---

## Rutas principales

| Ruta                        | Descripción                          |
|-----------------------------|--------------------------------------|
| `/dashboard`                | Dashboard principal                  |
| `/user/two-factor`          | Activar 2FA TOTP                     |
| `/auth/mastodon/redirect`   | OAuth Mastodon                       |
| `/oauth/reddit/redirect`    | OAuth Reddit                         |
| `/posts/create`             | Crear publicación                    |
| `/posts/queue`              | Ver publicaciones en cola            |
| `/posts/history`            | Historial de publicaciones           |
| `/schedules`                | CRUD de horarios                     |

---

## Flujo de publicación

1. ✍️ Crear publicación y seleccionar destinos (Mastodon/Reddit).  
2. ⏱️ Elegir **modo**: `now` (inmediata), `scheduled` (fecha/hora), `queue` (siguiente slot por horario).  
3. 🗃️ Se crean `posts` + `post_targets`.  
4. ⚡ Job `PublishPost` encola **sub-jobs**: `PublishToMastodon`, `PublishToReddit`.  
5. ✅ El **worker** publica y actualiza estados (pendiente → publicado/failed).

---

## Troubleshooting

- **Callback OAuth inválido** → revisa que `APP_URL` + ruta de callback coincidan con lo registrado en el proveedor.  
- **La cola no procesa** → confirma `QUEUE_CONNECTION=database` y que `php artisan queue:work` esté activo; verifica tablas `jobs`/`failed_jobs`.  
- **Reddit exige User-Agent** → define `REDDIT_USER_AGENT` con tu usuario.  
- **Scopes** → asegúrate de solicitar los necesarios (ej.: `identity`, `submit` para Reddit).

---

## Contribuir

1. Crea un branch desde `main`.  
2. Aplica cambios + tests.  
3. Abre un Pull Request describiendo el impacto.  

---

## ⚙️ Automatización en VM (cron + worker)

Para que las publicaciones **programadas** (scheduler/horarios) y las **instantáneas** (cola) se ejecuten automáticamente al encender la VM:

### 1) Cron (scheduler cada minuto)

**Archivo de referencia (en el repo):** `scripts/socialhub.cron`  
Contenido sugerido:

```cron
# Social Hub - ejecutar scheduler de Laravel cada minuto
* * * * * cd /vagrant/sites/social-hub && /usr/bin/php artisan schedule:run >> /vagrant/sites/social-hub/storage/logs/cron.log 2>&1
```

**Instalación en la VM (manual):**
```bash
# Dentro de la VM
cd /vagrant/sites/social-hub
crontab scripts/socialhub.cron
crontab -l   # verificar que se cargó
```

> Asegúrate de que el archivo esté en formato **LF** y termine con una **línea en blanco**. Ajusta la ruta de PHP con `which php` si no es `/usr/bin/php`.

### 2) systemd (worker de colas en background)

**Archivo de referencia (en el repo):** `scripts/socialhub-queue.service`  
Contenido sugerido:

```ini
[Unit]
Description=SocialHub Laravel Queue Worker
After=network.target mysql.service mariadb.service

[Service]
User=vagrant
Group=vagrant
Restart=always
RestartSec=2
WorkingDirectory=/vagrant/sites/social-hub
ExecStart=/usr/bin/php artisan queue:work --queue=default --tries=1 --timeout=90
StandardOutput=append:/vagrant/sites/social-hub/storage/logs/queue.log
StandardError=append:/vagrant/sites/social-hub/storage/logs/queue.err.log
KillSignal=SIGINT
TimeoutStopSec=60

[Install]
WantedBy=multi-user.target
```

**Instalación en la VM (manual):**
```bash
sudo cp scripts/socialhub-queue.service /etc/systemd/system/socialhub-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now socialhub-queue
sudo systemctl status socialhub-queue
```

### 3) Verificación rápida

```bash
# Cron / scheduler
sudo systemctl status cron
tail -f storage/logs/cron.log

# Queue / worker
sudo systemctl status socialhub-queue
tail -f storage/logs/queue.log storage/logs/queue.err.log
```

> **Comportamiento esperado:**  
> - **Instantáneas** → salen en segundos (worker de colas en systemd).  
> - **Programadas/horarios** → se liberan cada minuto (cron ejecuta `schedule:run`).  

---

## Archivos de referencia incluidos en el repo (no son config del sistema)

- `scripts/socialhub.cron` → plantilla para instalar con `crontab`.  
- `scripts/socialhub-queue.service` → plantilla para copiar a `/etc/systemd/system/`.  

> **No** se incluye en el repositorio el `crontab` real del usuario ni los archivos ya instalados en `/etc/systemd/system/`. Esos se configuran manualmente en la VM.
