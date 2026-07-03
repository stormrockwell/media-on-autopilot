#!/usr/bin/env bash
# Builds the exact distributable zip the WordPress.org deploy will publish.
# Mirrors the plugin into dist/ using .distignore, with production deps + built assets.
set -euo pipefail

SLUG="media-on-autopilot"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"

rm -rf "$DIST" "$ROOT/$SLUG.zip"
mkdir -p "$DIST/$SLUG"

# Production-only autoloader + freshly built assets.
composer install --no-dev --optimize-autoloader --working-dir="$ROOT"
( cd "$ROOT" && npm ci && npm run build )

# Mirror the shippable file set.
rsync -a --exclude-from="$ROOT/.distignore" "$ROOT/" "$DIST/$SLUG/"

( cd "$DIST" && zip -rq "$ROOT/$SLUG.zip" "$SLUG" )
echo "Built $ROOT/$SLUG.zip"

# Restore dev dependencies for local development.
composer install --working-dir="$ROOT"
