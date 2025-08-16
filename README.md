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