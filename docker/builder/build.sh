#!/usr/bin/env bash
set -euo pipefail

CSS=/app/web/public/dist/css/agendav.css
JS=/app/web/public/dist/js/agendav.min.js
VENDOR=/app/web/vendor/autoload.php

if [[ -f "$CSS" && -f "$JS" && -f "$VENDOR" && -z "${FORCE:-}" ]]; then
  echo "[builder] dist + vendor present, skipping build"
  exit 0
fi

cd /app
echo "[builder] npm install"
npm install --legacy-peer-deps --no-audit --no-fund

echo "[builder] composer install"
(cd web && composer install --prefer-dist --no-interaction --no-progress)

echo "[builder] build assets"
npm run build:templates
npm run build:css
npm run build:js

echo "[builder] build complete"
