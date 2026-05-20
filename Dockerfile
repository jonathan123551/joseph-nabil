# ============================================================================
# Dockerfile — production image for the elabeed-tickets Railway deployment.
# ============================================================================
# Replaces the previous `php:8.2-cli` + `php artisan serve` setup with a
# proper php-fpm worker pool fronted by nginx, both managed by supervisord.
#
# What changed and why:
#   * Base image: php:8.2-cli  →  php:8.2-fpm
#     The fpm image ships with the FastCGI process manager already
#     configured; the old setup launched PHP's built-in single-threaded
#     dev server, which queued every concurrent request linearly.
#
#   * Added: nginx + supervisord, configured via the repo's nginx.conf
#     and supervisord.conf (both updated in this same change).
#
#   * Added: Node 22 + the local Tailwind v4 build.
#     The app used to load `https://cdn.tailwindcss.com` at runtime
#     (Tailwind's "Play CDN", explicitly a dev-only tool — ~370 KB of
#     JS that recompiles CSS in every browser, every page load). We
#     now run `npm run build` during image build to emit a static,
#     minified `public/build/app.css` (~55 KB) that gets long-lived
#     Cache-Control via nginx.
#
#   * Added: `artisan config:cache` + `view:cache` so each request
#     boots Laravel from compiled metadata instead of re-parsing PHP
#     files. Skipped `route:cache` because some routes use closures.
#
# Visual identity is intentionally unchanged — same blade templates,
# same Tailwind utilities, same PRISM inline styles. This file is only
# about how the same bytes get served to the browser.
# ============================================================================

FROM php:8.2-fpm

# ----- System dependencies -------------------------------------------------
# nginx       — production HTTP front-end (was missing in the cli image)
# supervisor  — runs php-fpm + nginx side-by-side
# nodejs/npm  — needed at build time only, to compile Tailwind
# libpng/libjpeg/libfreetype/libpq — required by the gd + pdo_pgsql exts
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        ca-certificates \
        curl \
        gnupg \
        git unzip zip \
        libzip-dev \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# ----- PHP extensions ------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_pgsql

# Redis extension — keeps the option open to flip CACHE_STORE / SESSION_DRIVER
# to redis in a follow-up Tier-2 change without rebuilding the image.
RUN pecl install redis && docker-php-ext-enable redis

# ----- PHP runtime tuning --------------------------------------------------
# Upload limits matched to the admin's poster / ticket template flow.
# These are the same values the previous CLI image used; carried over so
# existing admin actions don't regress.
RUN { \
        echo "upload_max_filesize=25M"; \
        echo "post_max_size=30M"; \
        echo "memory_limit=256M"; \
        echo "max_execution_time=120"; \
        echo "max_input_time=120"; \
    } > /usr/local/etc/php/conf.d/zz-uploads.ini

# OPcache — recommended Laravel production settings. Without this, PHP
# re-parses every .php file on every request, which Laravel-style apps
# feel especially hard (lots of small files in vendor/).
RUN { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=0"; \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.interned_strings_buffer=16"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "opcache.validate_timestamps=0"; \
        echo "opcache.revalidate_freq=0"; \
        echo "opcache.fast_shutdown=1"; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini

# ----- Composer ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ----- App source ----------------------------------------------------------
WORKDIR /app
COPY . .

# ----- Composer install (prod) --------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ----- Tailwind build ------------------------------------------------------
# Runs the local v4 build (configured in package.json). Output:
# public/build/app.css — referenced by resources/views/layouts/app.blade.php
# via `asset('build/app.css')`. node_modules is removed after the build
# so the runtime image stays slim.
RUN npm install --no-audit --no-fund --prefer-offline \
    && npm run build \
    && rm -rf node_modules

# ----- Laravel runtime caches ---------------------------------------------
# Only `view:cache` is baked in here — it depends solely on .blade.php
# files already present in the image, so the result is deterministic
# and reusable across containers. `config:cache` is intentionally NOT
# run at image build time because Laravel would snapshot whatever .env
# happens to be on disk during build; the Railway environment variables
# get injected at container start and would be ignored if we cached
# config too early. `route:cache` is skipped because routes/web.php
# may use closure-based routes which Laravel rejects when caching.
RUN php artisan view:cache

# ----- nginx + supervisord wiring -----------------------------------------
# Replace the distro defaults with our tuned configs (gzip on, immutable
# cache for static assets, fastcgi -> 127.0.0.1:9000).
COPY nginx.conf       /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/supervisord.conf

# php-fpm pool listens on 127.0.0.1:9000 by default in php:8.2-fpm; we
# keep that and let nginx fastcgi_pass to it.

# ----- Filesystem permissions ---------------------------------------------
# www-data is the user php-fpm runs as in the php:fpm image; storage and
# bootstrap/cache must be writable by it for cache:write, log:write, etc.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && mkdir -p /var/log/supervisor /var/log/nginx

# ----- Port ----------------------------------------------------------------
EXPOSE 8080

# ----- Entrypoint ----------------------------------------------------------
# supervisord runs as PID 1, fans out to php-fpm + nginx. Crashes either
# child → supervisord restarts it; both children crash → container exits
# and Railway reschedules.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
