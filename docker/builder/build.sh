#!/usr/bin/env bash
set -euo pipefail

CSS=/app/resources/public/assets/dist/css/agendav.css
JS=/app/resources/public/assets/dist/js/agendav.min.js
VENDOR=/app/vendor/autoload.php

if [[ -f "$CSS" && -f "$JS" && -f "$VENDOR" && -z "${FORCE:-}" ]]; then
  echo "[builder] dist + vendor present, skipping build"
  exit 0
fi

cd /app
echo "[builder] npm ci"
npm ci --legacy-peer-deps --no-audit --no-fund

echo "[builder] composer install"
composer install --prefer-dist --no-interaction --no-progress

echo "[builder] build assets"
npm run build:templates
npm run build:copy
npm run build:css
npm run build:js

echo "[builder] build complete"
