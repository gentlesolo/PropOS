#!/usr/bin/env bash
# =============================================================================
#  PropOS Platform — Local Pre-Deploy Build Script
#  Run this on your LOCAL machine (Windows/Mac/Linux) BEFORE uploading files.
#  It compiles assets and prepares an upload-ready archive.
#
#  Usage: bash deploy/pre-deploy-build.sh [--zip] [--tar]
# =============================================================================
set -euo pipefail

CYAN='\033[0;36m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BOLD='\033[1m'; RESET='\033[0m'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
ARCHIVE_FORMAT="none"

for arg in "$@"; do
    case $arg in
        --zip) ARCHIVE_FORMAT="zip" ;;
        --tar) ARCHIVE_FORMAT="tar" ;;
    esac
done

echo -e "${BOLD}${CYAN}PropOS Platform — Local Pre-Deploy Build${RESET}\n"

# â”€â”€ 1. Install all deps (including dev for build) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "${CYAN}â–¶ Installing npm dependencies...${RESET}"
cd "$ROOT_DIR"
npm install

# â”€â”€ 2. Build production assets â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "${CYAN}â–¶ Building production assets...${RESET}"
npm run build
echo -e "${GREEN}âœ” Assets built: $ROOT_DIR/public/build/${RESET}"

# â”€â”€ 3. Install Composer deps (no-dev for upload) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "${CYAN}â–¶ Installing Composer dependencies (no-dev)...${RESET}"
composer install --no-dev --optimize-autoloader --prefer-dist --quiet
echo -e "${GREEN}âœ” Composer dependencies installed (production only)${RESET}"

# â”€â”€ 4. Create upload archive (optional) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if [[ "$ARCHIVE_FORMAT" != "none" ]]; then
    mkdir -p "$DIST_DIR"
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    ARCHIVE_NAME="PropOS_${TIMESTAMP}"

    EXCLUDES=(
        ".git"
        ".gitignore"
        "node_modules"
        "dist"
        "deploy/*.sh"
        "tests"
        "phpunit.xml"
        ".env"
        "database/database.sqlite"
        "storage/logs/*.log"
        "storage/framework/cache/data/*"
        "storage/framework/sessions/*"
        "storage/framework/views/*"
    )

    if [[ "$ARCHIVE_FORMAT" == "zip" ]]; then
        ARCHIVE="$DIST_DIR/${ARCHIVE_NAME}.zip"
        EXCLUDE_ARGS=()
        for exc in "${EXCLUDES[@]}"; do
            EXCLUDE_ARGS+=("--exclude=$exc")
        done
        zip -r "${EXCLUDE_ARGS[@]}" "$ARCHIVE" . -x "*.DS_Store"
        echo -e "${GREEN}âœ” Archive: $ARCHIVE${RESET}"

    elif [[ "$ARCHIVE_FORMAT" == "tar" ]]; then
        ARCHIVE="$DIST_DIR/${ARCHIVE_NAME}.tar.gz"
        EXCLUDE_ARGS=()
        for exc in "${EXCLUDES[@]}"; do
            EXCLUDE_ARGS+=("--exclude=./$exc")
        done
        tar -czf "$ARCHIVE" "${EXCLUDE_ARGS[@]}" --exclude="./*.DS_Store" -C "$(dirname "$ROOT_DIR")" "$(basename "$ROOT_DIR")"
        echo -e "${GREEN}âœ” Archive: $ARCHIVE${RESET}"
    fi

    echo -e "\n${YELLOW}Upload instructions:${RESET}"
    echo -e "  1. Upload ${BOLD}$ARCHIVE${RESET} to your server (outside public_html)"
    echo -e "  2. Extract: tar xzf $ARCHIVE_NAME.tar.gz  OR  unzip $ARCHIVE_NAME.zip"
    echo -e "  3. SSH into server and run: bash PropOS/deploy/deploy.sh --first-deploy"
else
    echo -e "\n${YELLOW}Next steps:${RESET}"
    echo -e "  1. Upload the entire project directory to your server (outside public_html)"
    echo -e "     Tip: Use FileZilla, rsync, or Git to transfer files."
    echo -e "  2. SSH in and run: bash PropOS/deploy/deploy.sh --first-deploy"
fi

echo -e "\n${YELLOW}Files to upload (important):${RESET}"
echo -e "  âœ” app/           vendor/         config/"
echo -e "  âœ” bootstrap/     routes/         database/"
echo -e "  âœ” resources/     storage/        public/"
echo -e "  âœ” artisan        composer.json   .env.example"
echo -e "  âœ” deploy/        (this scripts folder)"
echo -e "  âœ— node_modules/  .git/           .env  (do NOT upload)"

echo -e "\n${GREEN}Build complete!${RESET}"
