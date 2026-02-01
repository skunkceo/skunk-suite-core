#!/bin/bash
#
# Skunk Suite Core - Build Script
#
# Copies the suite-core package into a target plugin's includes/suite/ directory.
#
# Usage:
#   ./build.sh /path/to/plugin
#   ./build.sh ../skunkcrm-plugin
#   ./build.sh ../skunkforms-free-plugin
#

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
TARGET_DIR="${1:?Usage: build.sh /path/to/target-plugin}"

# Resolve target
TARGET_DIR="$(cd "$TARGET_DIR" 2>/dev/null && pwd)" || {
    echo "Error: Target directory not found: $1"
    exit 1
}

DEST="$TARGET_DIR/includes/suite"

echo "📦 Skunk Suite Core → $DEST"

# Create destination
mkdir -p "$DEST/src"

# Copy files
cp "$SCRIPT_DIR/loader.php" "$DEST/loader.php"
cp "$SCRIPT_DIR/src/class-skunk-icons.php" "$DEST/src/class-skunk-icons.php"
cp "$SCRIPT_DIR/src/class-skunk-product-detect.php" "$DEST/src/class-skunk-product-detect.php"
cp "$SCRIPT_DIR/src/class-skunk-dashboard.php" "$DEST/src/class-skunk-dashboard.php"
cp "$SCRIPT_DIR/src/class-skunk-masterbar.php" "$DEST/src/class-skunk-masterbar.php"
cp "$SCRIPT_DIR/src/class-skunk-menu.php" "$DEST/src/class-skunk-menu.php"
cp "$SCRIPT_DIR/src/class-skunk-license-manager.php" "$DEST/src/class-skunk-license-manager.php"
cp "$SCRIPT_DIR/src/class-skunk-update-checker.php" "$DEST/src/class-skunk-update-checker.php"

echo "✅ Done. Suite core installed to $DEST"
echo ""
echo "Add to your plugin's bootstrap:"
echo "  require_once __DIR__ . '/includes/suite/loader.php';"
