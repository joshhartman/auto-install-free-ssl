#!/usr/bin/env bash
# Builds a WordPress-installable plugin zip with the plugin folder as root.
# Usage: ./build-zip.sh [version]  (defaults to latest git tag)
set -euo pipefail

PLUGIN="auto-install-free-ssl"
VERSION="${1:-$(git describe --tags --abbrev=0 2>/dev/null || echo dev)}"
OUT="${PLUGIN}.${VERSION}.zip"

git archive --format=zip --prefix="${PLUGIN}/" -o "$OUT" HEAD
echo "Wrote ${OUT}"
