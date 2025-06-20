#!/bin/bash

# CWP Chat Bubbles - Plugin Packaging Script
# Creates a clean WordPress installation ZIP file

set -e  # Exit on any error

echo "🚀 CWP Chat Bubbles - Plugin Packaging Script"
echo "================================================"

# Configuration
PLUGIN_NAME="cwp-chat-bubbles"
TEMP_DIR="/tmp/${PLUGIN_NAME}-package"
ZIP_NAME="${PLUGIN_NAME}.zip"
CURRENT_DIR=$(pwd)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}📦 Preparing plugin package...${NC}"

# Clean up any existing temp directory
if [ -d "$TEMP_DIR" ]; then
    echo -e "${YELLOW}🧹 Cleaning up existing temp directory...${NC}"
    rm -rf "$TEMP_DIR"
fi

# Create temp directory structure
echo -e "${BLUE}📁 Creating package directory...${NC}"
mkdir -p "$TEMP_DIR/$PLUGIN_NAME"

# Run production build first
echo -e "${BLUE}🔨 Building production assets...${NC}"
if ! pnpm run build; then
    echo -e "${RED}❌ Production build failed!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Production build completed${NC}"

# Copy main plugin file
echo -e "${BLUE}📄 Copying main plugin file...${NC}"
cp "$CURRENT_DIR/cwp-chat-bubbles.php" "$TEMP_DIR/$PLUGIN_NAME/"

# Copy includes directory
echo -e "${BLUE}📁 Copying includes directory...${NC}"
if [ -d "$CURRENT_DIR/includes" ]; then
    cp -r "$CURRENT_DIR/includes" "$TEMP_DIR/$PLUGIN_NAME/"
else
    echo -e "${RED}❌ includes directory not found!${NC}"
    exit 1
fi

# Copy compiled assets directory
echo -e "${BLUE}🎨 Copying compiled assets...${NC}"
if [ -d "$CURRENT_DIR/assets" ]; then
    cp -r "$CURRENT_DIR/assets" "$TEMP_DIR/$PLUGIN_NAME/"
else
    echo -e "${RED}❌ assets directory not found!${NC}"
    exit 1
fi

# Copy admin directory (if exists)
if [ -d "$CURRENT_DIR/admin" ]; then
    echo -e "${BLUE}⚙️  Copying admin directory...${NC}"
    cp -r "$CURRENT_DIR/admin" "$TEMP_DIR/$PLUGIN_NAME/"
fi

# Copy templates directory (if exists)
if [ -d "$CURRENT_DIR/templates" ]; then
    echo -e "${BLUE}📋 Copying templates directory...${NC}"
    cp -r "$CURRENT_DIR/templates" "$TEMP_DIR/$PLUGIN_NAME/"
fi

# Copy README.md (if exists)
if [ -f "$CURRENT_DIR/README.md" ]; then
    echo -e "${BLUE}📖 Copying README.md...${NC}"
    cp "$CURRENT_DIR/README.md" "$TEMP_DIR/$PLUGIN_NAME/"
fi

# Create basic uninstall.php if it doesn't exist
if [ ! -f "$TEMP_DIR/$PLUGIN_NAME/uninstall.php" ]; then
    echo -e "${BLUE}🗑️  Creating uninstall.php...${NC}"
    cat > "$TEMP_DIR/$PLUGIN_NAME/uninstall.php" << 'EOF'
<?php
/**
 * CWP Chat Bubbles Uninstall
 *
 * @package CWP_Chat_Bubbles
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('cwp_chat_bubbles_settings');
delete_option('cwp_chat_bubbles_version');

// Clear any cached data
wp_cache_flush();
EOF
fi

# Create the ZIP file
echo -e "${BLUE}📦 Creating ZIP file...${NC}"
cd "$TEMP_DIR"

# Remove any existing ZIP file
if [ -f "$CURRENT_DIR/$ZIP_NAME" ]; then
    echo -e "${YELLOW}🗑️  Removing existing ZIP file...${NC}"
    rm "$CURRENT_DIR/$ZIP_NAME"
fi

# Create ZIP file
if zip -r "$CURRENT_DIR/$ZIP_NAME" "$PLUGIN_NAME" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ ZIP file created successfully!${NC}"
else
    echo -e "${RED}❌ Failed to create ZIP file!${NC}"
    exit 1
fi

# Clean up temp directory
echo -e "${BLUE}🧹 Cleaning up temporary files...${NC}"
cd "$CURRENT_DIR"
rm -rf "$TEMP_DIR"

# Get file size
ZIP_SIZE=$(ls -lh "$ZIP_NAME" | awk '{print $5}')

echo ""
echo -e "${GREEN}🎉 Plugin packaging completed successfully!${NC}"
echo "================================================"
echo -e "${GREEN}📦 Package: ${NC}$ZIP_NAME"
echo -e "${GREEN}📏 Size: ${NC}$ZIP_SIZE"
echo -e "${GREEN}📍 Location: ${NC}$CURRENT_DIR/$ZIP_NAME"
echo ""
echo -e "${BLUE}📋 Package Contents:${NC}"
echo "  ├── cwp-chat-bubbles.php (main plugin file)"
echo "  ├── includes/ (PHP classes)"
echo "  ├── admin/ (admin interface files)"
echo "  ├── assets/ (compiled CSS & JS)"
echo "  ├── templates/ (frontend templates)"
echo "  ├── uninstall.php (cleanup script)"
echo "  └── README.md (documentation)"
echo ""
echo -e "${YELLOW}🚀 Ready for WordPress installation!${NC}"
echo -e "${YELLOW}💡 Upload this ZIP file through WordPress Admin > Plugins > Add New > Upload Plugin${NC}"
echo "" 