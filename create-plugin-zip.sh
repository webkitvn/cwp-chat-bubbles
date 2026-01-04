#!/bin/bash
#
# CWP Chat Bubbles - Plugin Packaging Script
# Creates a clean WordPress installation ZIP file
#
# Usage: ./create-plugin-zip.sh [--skip-build]
#

set -Eeuo pipefail
IFS=$'\n\t'

# Configuration
PLUGIN_NAME="cwp-chat-bubbles"
ZIP_NAME="${PLUGIN_NAME}.zip"
CURRENT_DIR=$(pwd)
TEMP_DIR=""
SKIP_BUILD=false

# Colors (disabled if not a TTY or NO_COLOR is set)
if [ -n "${NO_COLOR:-}" ] || [ ! -t 1 ]; then
    RED='' GREEN='' YELLOW='' BLUE='' NC=''
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    NC='\033[0m'
fi

# Helper functions
log_info()  { printf "%b\n" "${BLUE}$1${NC}"; }
log_ok()    { printf "%b\n" "${GREEN}$1${NC}"; }
log_warn()  { printf "%b\n" "${YELLOW}$1${NC}"; }
log_error() { printf "%b\n" "${RED}$1${NC}" >&2; }

cleanup() {
    if [ -n "$TEMP_DIR" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
}
trap cleanup EXIT

copy_required() {
    local name=$1
    local src=$2
    if [ -d "$src" ]; then
        log_info "📁 Copying ${name}..."
        cp -r "$src" "$TEMP_DIR/$PLUGIN_NAME/"
    else
        log_error "❌ Required directory not found: $src"
        exit 1
    fi
}

copy_optional() {
    local name=$1
    local src=$2
    if [ -d "$src" ]; then
        log_info "📁 Copying ${name}..."
        cp -r "$src" "$TEMP_DIR/$PLUGIN_NAME/"
    fi
}

copy_optional_file() {
    local name=$1
    local src=$2
    if [ -f "$src" ]; then
        log_info "📄 Copying ${name}..."
        cp "$src" "$TEMP_DIR/$PLUGIN_NAME/"
    fi
}

# Parse arguments
for arg in "$@"; do
    case $arg in
        --skip-build) SKIP_BUILD=true ;;
        -h|--help)
            echo "Usage: $0 [--skip-build]"
            echo "  --skip-build  Skip the production build step"
            exit 0
            ;;
        *)
            log_error "Unknown option: $arg"
            exit 1
            ;;
    esac
done

# Check required commands
for cmd in pnpm zip; do
    command -v "$cmd" >/dev/null 2>&1 || {
        log_error "❌ Required command not found: $cmd"
        exit 1
    }
done

# Validate we're in the plugin root
[ -f "cwp-chat-bubbles.php" ] || {
    log_error "❌ cwp-chat-bubbles.php not found. Run from plugin root."
    exit 1
}

echo "🚀 CWP Chat Bubbles - Plugin Packaging Script"
echo "================================================"

# Create temp directory
TEMP_DIR=$(mktemp -d "/tmp/${PLUGIN_NAME}-package.XXXXXX")
mkdir -p "$TEMP_DIR/$PLUGIN_NAME"

# Run production build
if [ "$SKIP_BUILD" = false ]; then
    log_info "🔨 Building production assets..."
    pnpm run build || {
        log_error "❌ Production build failed!"
        exit 1
    }
    log_ok "✅ Production build completed"
else
    log_warn "⏭️  Skipping build (--skip-build)"
fi

# Copy files
log_info "📄 Copying main plugin file..."
cp "$CURRENT_DIR/cwp-chat-bubbles.php" "$TEMP_DIR/$PLUGIN_NAME/"

copy_required "includes" "$CURRENT_DIR/includes"
copy_required "assets" "$CURRENT_DIR/assets"
copy_optional "admin" "$CURRENT_DIR/admin"
copy_optional "templates" "$CURRENT_DIR/templates"
copy_optional_file "README.md" "$CURRENT_DIR/README.md"
copy_optional_file "uninstall.php" "$CURRENT_DIR/uninstall.php"

# Generate uninstall.php if not present
if [ ! -f "$TEMP_DIR/$PLUGIN_NAME/uninstall.php" ]; then
    log_info "🗑️  Generating uninstall.php..."
    cat > "$TEMP_DIR/$PLUGIN_NAME/uninstall.php" << 'EOF'
<?php
/**
 * CWP Chat Bubbles Uninstall
 *
 * @package CWP_Chat_Bubbles
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('cwp_chat_bubbles_settings');
delete_option('cwp_chat_bubbles_version');
wp_cache_flush();
EOF
fi

# Create ZIP file
log_info "📦 Creating ZIP file..."
cd "$TEMP_DIR" || exit 1

[ -f "$CURRENT_DIR/$ZIP_NAME" ] && rm "$CURRENT_DIR/$ZIP_NAME"

zip -rq "$CURRENT_DIR/$ZIP_NAME" "$PLUGIN_NAME" || {
    log_error "❌ Failed to create ZIP file!"
    exit 1
}

cd "$CURRENT_DIR" || exit 1

# Get file size
ZIP_SIZE=$(ls -lh "$ZIP_NAME" | awk '{print $5}')

echo ""
log_ok "🎉 Plugin packaging completed!"
echo "================================================"
printf "%b%s%b %s\n" "$GREEN" "📦 Package:" "$NC" "$ZIP_NAME"
printf "%b%s%b %s\n" "$GREEN" "📏 Size:" "$NC" "$ZIP_SIZE"
printf "%b%s%b %s\n" "$GREEN" "📍 Location:" "$NC" "$CURRENT_DIR/$ZIP_NAME"
echo ""
log_warn "💡 Upload via WordPress Admin > Plugins > Add New > Upload Plugin"
