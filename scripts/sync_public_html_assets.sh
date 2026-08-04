#!/bin/bash
# Sincroniza public/assets → public_html/assets (document root SiteGround)
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
rsync -av --delete "$ROOT/public/assets/" "$ROOT/public_html/assets/"
echo "SYNC_OK: public/assets -> public_html/assets"
