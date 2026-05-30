#!/usr/bin/env bash
# =============================================================================
#  PropOS Platform — Shared Hosting Deploy Script
#  Tested on: cPanel / Hostinger / Namecheap / SiteGround / Bluehost
#  Requires : SSH access, PHP 8.3+, Composer (auto-downloaded if missing)
#
#  Usage:
#    bash deploy.sh                  # auto-detect first vs update
#    bash deploy.sh --first-deploy   # force first-deploy mode
#    bash deploy.sh --update         # force update mode
#    bash deploy.sh --build-assets   # build assets locally, then deploy
#    bash deploy.sh --help
# =============================================================================
set -euo pipefail

# â”€â”€ Colours â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

print_header()  { echo -e "\n${BOLD}${BLUE}â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•${RESET}"; echo -e "${BOLD}${BLUE}  $1${RESET}"; echo -e "${BOLD}${BLUE}â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•${RESET}"; }
print_step()    { echo -e "\n${CYAN}â–¶ $1${RESET}"; }
print_success() { echo -e "${GREEN}âœ” $1${RESET}"; }
print_warning() { echo -e "${YELLOW}âš  $1${RESET}"; }
print_error()   { echo -e "${RED}âœ– $1${RESET}" >&2; }
print_info()    { echo -e "  ${BLUE}â†’${RESET} $1"; }

# â”€â”€ Config (edit these before running) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PUBLIC_HTML_DIR="${PUBLIC_HTML_DIR:-}"          # e.g. /home/username/public_html
PHP_BIN="${PHP_BIN:-}"                          # auto-detected if empty
COMPOSER_BIN="${COMPOSER_BIN:-}"               # auto-detected if empty
DEPLOY_MODE="${DEPLOY_MODE:-auto}"              # auto | first-deploy | update
BUILD_ASSETS="${BUILD_ASSETS:-false}"

# â”€â”€ Argument parsing â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
for arg in "$@"; do
    case $arg in
        --first-deploy) DEPLOY_MODE="first-deploy" ;;
        --update)       DEPLOY_MODE="update" ;;
        --build-assets) BUILD_ASSETS="true" ;;
        --help|-h)
            echo "Usage: bash deploy.sh [--first-deploy] [--update] [--build-assets]"
            echo ""
            echo "  --first-deploy   Run full setup (generate key, seed, etc.)"
            echo "  --update         Update existing deployment"
            echo "  --build-assets   Build frontend assets before deploying"
            echo ""
            echo "Environment variables:"
            echo "  APP_DIR          Laravel root directory (default: parent of this script)"
            echo "  PUBLIC_HTML_DIR  Web root directory (e.g. /home/user/public_html)"
            echo "  PHP_BIN          Path to PHP 8.3+ binary"
            echo "  COMPOSER_BIN     Path to Composer binary"
            exit 0
            ;;
        *) print_warning "Unknown argument: $arg" ;;
    esac
done

# â”€â”€ Helper: run artisan â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
artisan() { "$PHP_BIN" "$APP_DIR/artisan" "$@"; }

# â”€â”€ Step 1: PHP detection â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
detect_php() {
    print_step "Detecting PHP binary"

    if [[ -n "$PHP_BIN" ]]; then
        print_info "Using PHP_BIN=$PHP_BIN"
        return
    fi

    # Common shared-hosting PHP binary locations
    local candidates=(
        "/usr/local/bin/php83"
        "/usr/local/bin/php8.3"
        "/usr/bin/php8.3"
        "/opt/php83/bin/php"
        "/usr/local/php83/bin/php"
        "php83"
        "php8.3"
        "php"
    )

    for bin in "${candidates[@]}"; do
        if command -v "$bin" &>/dev/null; then
            local version
            version=$("$bin" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0")
            local major minor
            major=$(echo "$version" | cut -d. -f1)
            minor=$(echo "$version" | cut -d. -f2)
            if [[ "$major" -gt 8 ]] || [[ "$major" -eq 8 && "$minor" -ge 3 ]]; then
                PHP_BIN=$(command -v "$bin")
                print_success "Found PHP $version at $PHP_BIN"
                return
            fi
        fi
    done

    print_error "PHP 8.3+ not found. Set PHP_BIN=/path/to/php83 and retry."
    print_info  "On cPanel: try 'ls /usr/local/bin/php*' to list available versions."
    exit 1
}

# â”€â”€ Step 2: Required PHP extensions check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
check_extensions() {
    print_step "Checking PHP extensions"

    local required=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer"
                    "xml" "ctype" "json" "bcmath" "fileinfo" "curl" "zip")
    local missing=()

    for ext in "${required[@]}"; do
        if ! "$PHP_BIN" -m 2>/dev/null | grep -qi "^${ext}$"; then
            missing+=("$ext")
        fi
    done

    if [[ ${#missing[@]} -gt 0 ]]; then
        print_warning "Missing PHP extensions: ${missing[*]}"
        print_info "Enable them via cPanel â†’ PHP Selector or php.ini"
    else
        print_success "All required PHP extensions present"
    fi
}

# â”€â”€ Step 3: Composer detection / download â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
detect_composer() {
    print_step "Detecting Composer"

    if [[ -n "$COMPOSER_BIN" ]]; then
        print_info "Using COMPOSER_BIN=$COMPOSER_BIN"
        return
    fi

    if command -v composer &>/dev/null; then
        COMPOSER_BIN="composer"
        print_success "Found system Composer: $(composer --version --no-ansi 2>&1 | head -1)"
        return
    fi

    if [[ -f "$APP_DIR/composer.phar" ]]; then
        COMPOSER_BIN="$PHP_BIN $APP_DIR/composer.phar"
        print_success "Found local composer.phar"
        return
    fi

    print_warning "Composer not found. Downloading composer.phar..."
    "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "$PHP_BIN" composer-setup.php --install-dir="$APP_DIR" --filename=composer.phar --quiet
    rm -f composer-setup.php
    COMPOSER_BIN="$PHP_BIN $APP_DIR/composer.phar"
    print_success "Downloaded Composer to $APP_DIR/composer.phar"
}

# â”€â”€ Step 4: Environment setup â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
setup_env() {
    print_step "Setting up .env"

    if [[ ! -f "$APP_DIR/.env" ]]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        print_success "Created .env from .env.example"
        print_warning "IMPORTANT: Edit $APP_DIR/.env with your production values before continuing."
        print_info "Key variables to set:"
        print_info "  APP_ENV=production"
        print_info "  APP_DEBUG=false"
        print_info "  APP_URL=https://yourdomain.com"
        print_info "  DB_CONNECTION=mysql"
        print_info "  DB_HOST=localhost (or your MySQL host)"
        print_info "  DB_DATABASE=your_db_name"
        print_info "  DB_USERNAME=your_db_user"
        print_info "  DB_PASSWORD=your_db_password"
        print_info "  MAIL_MAILER=smtp"
        print_info "  STRIPE_* and AI API keys"
        echo ""
        read -r -p "  Press ENTER after editing .env to continue, or Ctrl+C to abort: "
    else
        print_success ".env already exists"
    fi

    # Validate critical .env values
    local app_key
    app_key=$(grep -E "^APP_KEY=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"')
    if [[ -z "$app_key" ]]; then
        print_info "Generating application key..."
        artisan key:generate --force
        print_success "APP_KEY generated"
    fi

    local app_env
    app_env=$(grep -E "^APP_ENV=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"')
    if [[ "$app_env" == "local" ]]; then
        print_warning "APP_ENV is still 'local'. Consider setting it to 'production'."
    fi

    local app_debug
    app_debug=$(grep -E "^APP_DEBUG=" "$APP_DIR/.env" | cut -d= -f2- | tr -d '"')
    if [[ "$app_debug" == "true" ]]; then
        print_warning "APP_DEBUG is 'true'. Set to 'false' in production to hide errors."
    fi
}

# â”€â”€ Step 5: Install Composer dependencies â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
install_deps() {
    print_step "Installing Composer dependencies (production, no-dev)"

    cd "$APP_DIR"
    $COMPOSER_BIN install \
        --no-dev \
        --no-interaction \
        --optimize-autoloader \
        --prefer-dist \
        --quiet

    print_success "Composer dependencies installed"
}

# â”€â”€ Step 6: Build frontend assets (optional, local step) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
build_assets() {
    if [[ "$BUILD_ASSETS" != "true" ]]; then
        if [[ ! -d "$APP_DIR/public/build" ]]; then
            print_warning "No public/build directory found."
            print_info "Run locally: npm install && npm run build"
            print_info "Then upload public/build/ to the server."
            print_info "Or re-run with --build-assets if Node.js is available on this server."
        else
            print_success "Found pre-built assets in public/build/"
        fi
        return
    fi

    print_step "Building frontend assets"

    if ! command -v node &>/dev/null; then
        print_error "Node.js not found. Build assets locally and upload public/build/"
        return
    fi

    cd "$APP_DIR"
    npm install --silent
    npm run build
    print_success "Frontend assets built"
}

# â”€â”€ Step 7: Database migrations â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
run_migrations() {
    print_step "Running database migrations"

    # Test DB connection first
    if ! artisan migrate:status --no-ansi &>/dev/null; then
        print_error "Cannot connect to database. Check DB_* values in .env"
        print_info "Common shared hosting MySQL host: localhost or 127.0.0.1"
        exit 1
    fi

    artisan migrate --force --no-interaction
    print_success "Migrations applied"
}

# â”€â”€ Step 8: Seeding (first deploy only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
run_seeders() {
    print_step "Running database seeders"
    artisan db:seed --force --no-interaction
    print_success "Seeders complete"
}

# â”€â”€ Step 9: Cache optimisation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
optimize_app() {
    print_step "Optimising application"

    artisan config:cache
    print_info "Config cached"

    artisan route:cache
    print_info "Routes cached"

    artisan view:cache
    print_info "Views cached"

    artisan event:cache
    print_info "Events cached"

    print_success "Application optimised"
}

# â”€â”€ Step 10: Storage link â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
setup_storage_link() {
    print_step "Setting up storage symlink"

    if [[ -L "$APP_DIR/public/storage" ]]; then
        print_success "Storage symlink already exists"
    else
        artisan storage:link
        print_success "Storage symlink created"
    fi
}

# â”€â”€ Step 11: File permissions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
set_permissions() {
    print_step "Setting file permissions"

    # Directories: 755, files: 644
    find "$APP_DIR" -type d -not -path "$APP_DIR/vendor/*" \
        -not -path "$APP_DIR/.git/*" \
        -exec chmod 755 {} \; 2>/dev/null || true

    find "$APP_DIR" -type f -not -path "$APP_DIR/vendor/*" \
        -not -path "$APP_DIR/.git/*" \
        -exec chmod 644 {} \; 2>/dev/null || true

    # Writable directories: 775
    local writable_dirs=(
        "$APP_DIR/storage"
        "$APP_DIR/storage/app"
        "$APP_DIR/storage/app/public"
        "$APP_DIR/storage/framework"
        "$APP_DIR/storage/framework/cache"
        "$APP_DIR/storage/framework/sessions"
        "$APP_DIR/storage/framework/views"
        "$APP_DIR/storage/logs"
        "$APP_DIR/bootstrap/cache"
    )

    for dir in "${writable_dirs[@]}"; do
        if [[ -d "$dir" ]]; then
            chmod -R 775 "$dir"
        fi
    done

    # Ensure artisan is executable
    chmod 755 "$APP_DIR/artisan"

    print_success "Permissions set (dirs 755, files 644, storage/cache 775)"
}

# â”€â”€ Step 12: Public directory / web root setup â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
setup_public_dir() {
    print_step "Setting up public web root"

    if [[ -z "$PUBLIC_HTML_DIR" ]]; then
        print_warning "PUBLIC_HTML_DIR not set. Skipping web-root setup."
        print_info "Set PUBLIC_HTML_DIR=/home/username/public_html and re-run, or"
        print_info "manually copy deploy/public-index.php â†’ public_html/index.php"
        print_info "and copy public/.htaccess â†’ public_html/.htaccess"
        return
    fi

    if [[ ! -d "$PUBLIC_HTML_DIR" ]]; then
        print_error "PUBLIC_HTML_DIR=$PUBLIC_HTML_DIR does not exist"
        exit 1
    fi

    # Symlink approach (try first — fastest)
    if ln -sfn "$APP_DIR/public" "${PUBLIC_HTML_DIR}" 2>/dev/null; then
        print_success "Symlinked $APP_DIR/public â†’ $PUBLIC_HTML_DIR (symlink approach)"
        return
    fi

    # Bridge approach — copy index.php + .htaccess, symlink build/
    print_info "Symlink failed; using bridge index.php approach"

    local bridge_index="$APP_DIR/deploy/public-index.php"
    if [[ ! -f "$bridge_index" ]]; then
        print_error "Bridge index not found: $bridge_index"
        exit 1
    fi

    cp "$bridge_index" "$PUBLIC_HTML_DIR/index.php"
    print_info "Copied bridge index.php"

    cp "$APP_DIR/public/.htaccess" "$PUBLIC_HTML_DIR/.htaccess"
    print_info "Copied .htaccess"

    # Sync static files
    for f in favicon.ico robots.txt; do
        [[ -f "$APP_DIR/public/$f" ]] && cp "$APP_DIR/public/$f" "$PUBLIC_HTML_DIR/$f"
    done

    # Symlink or copy build/ assets
    if ln -sfn "$APP_DIR/public/build" "$PUBLIC_HTML_DIR/build" 2>/dev/null; then
        print_info "Symlinked public/build â†’ $PUBLIC_HTML_DIR/build"
    elif [[ -d "$APP_DIR/public/build" ]]; then
        cp -r "$APP_DIR/public/build" "$PUBLIC_HTML_DIR/build"
        print_info "Copied public/build â†’ $PUBLIC_HTML_DIR/build"
    fi

    # Symlink storage
    if [[ -d "$APP_DIR/public/storage" ]]; then
        ln -sfn "$APP_DIR/public/storage" "$PUBLIC_HTML_DIR/storage" 2>/dev/null || \
            cp -r "$APP_DIR/public/storage" "$PUBLIC_HTML_DIR/storage"
        print_info "Linked storage"
    fi

    print_success "Public web root configured at $PUBLIC_HTML_DIR"
}

# â”€â”€ Step 13: .user.ini / php.ini for shared hosting â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
setup_php_ini() {
    print_step "Installing PHP configuration for public_html"

    local dest_dir="${PUBLIC_HTML_DIR:-$APP_DIR/public}"
    local ini_src="$APP_DIR/deploy/php.ini.shared"

    if [[ -f "$ini_src" ]]; then
        cp "$ini_src" "$dest_dir/.user.ini"
        cp "$ini_src" "$dest_dir/php.ini"
        print_success "PHP ini deployed to $dest_dir"
    fi
}

# â”€â”€ Step 14: Maintenance mode helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
enable_maintenance()  { artisan down --render="errors.503" --retry=60; print_success "Maintenance mode ON"; }
disable_maintenance() { artisan up; print_success "Maintenance mode OFF"; }

# â”€â”€ Step 15: Cron job instructions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
show_cron_instructions() {
    print_header "Cron Job Setup (Required)"

    echo -e "${BOLD}Add these cron entries in cPanel â†’ Cron Jobs:${RESET}"
    echo ""
    echo -e "${YELLOW}1. Laravel Scheduler (every minute):${RESET}"
    echo -e "   ${CYAN}* * * * * $PHP_BIN $APP_DIR/artisan schedule:run >> /dev/null 2>&1${RESET}"
    echo ""
    echo -e "${YELLOW}2. Queue Worker — shared-hosting mode (every minute):${RESET}"
    echo -e "   ${CYAN}* * * * * $PHP_BIN $APP_DIR/artisan queue:work --stop-when-empty --tries=3 --timeout=90 --max-jobs=20 >> $APP_DIR/storage/logs/queue-cron.log 2>&1${RESET}"
    echo ""
    echo -e "${BOLD}Note:${RESET} True persistent queue workers (queue:work without --stop-when-empty)"
    echo -e "require VPS/dedicated servers. The cron approach above processes jobs every"
    echo -e "minute and is the standard workaround for shared hosting."
    echo ""
    echo -e "${YELLOW}3. Daily cleanup (optional, 3am):${RESET}"
    echo -e "   ${CYAN}0 3 * * * $PHP_BIN $APP_DIR/artisan platform:cleanup >> /dev/null 2>&1${RESET}"
}

# â”€â”€ Post-deploy verification â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
post_deploy_check() {
    print_header "Post-Deploy Verification"

    local ok=true

    check() {
        local label="$1"; local cmd="$2"
        if eval "$cmd" &>/dev/null; then
            print_success "$label"
        else
            print_error "$label FAILED"
            ok=false
        fi
    }

    check ".env exists"            "[[ -f '$APP_DIR/.env' ]]"
    check "vendor/ exists"         "[[ -d '$APP_DIR/vendor' ]]"
    check "bootstrap/cache writable" "[[ -w '$APP_DIR/bootstrap/cache' ]]"
    check "storage/logs writable"  "[[ -w '$APP_DIR/storage/logs' ]]"
    check "storage/framework/cache writable" "[[ -w '$APP_DIR/storage/framework/cache' ]]"
    check "DB connection OK"       "$PHP_BIN $APP_DIR/artisan migrate:status --no-ansi"
    check "Config cached"          "[[ -f '$APP_DIR/bootstrap/cache/config.php' ]]"
    check "Routes cached"          "[[ -f '$APP_DIR/bootstrap/cache/routes-v7.php' ]] || [[ -f '$APP_DIR/bootstrap/cache/routes.php' ]]"
    check "Storage symlink"        "[[ -L '$APP_DIR/public/storage' ]]"
    check "public/build exists"    "[[ -d '$APP_DIR/public/build' ]]"

    echo ""
    if $ok; then
        print_success "All checks passed. Application is ready."
    else
        print_error "Some checks failed — review the output above."
    fi
}

# â”€â”€ Helper: clean deployment cache â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
clean_deployment_cache() {
    print_step "Cleaning deployment cache and verifying composer packages"

    "$PHP_BIN" -r '
        $root = $argv[1];
        $installedJson = "$root/vendor/composer/installed.json";
        if (file_exists($installedJson)) {
            try {
                $data = json_decode(file_get_contents($installedJson), true);
                if ($data && (isset($data["packages"]) || is_array($data))) {
                    $packages = $data["packages"] ?? $data;
                    $cleaned = [];
                    foreach ($packages as $pkg) {
                        $installPath = $pkg["install-path"] ?? "";
                        if ($installPath) {
                            $absolutePath = rtrim("$root/vendor/composer/$installPath", "/\\");
                            if (is_dir($absolutePath)) {
                                $cleaned[] = $pkg;
                            }
                        } else {
                            $cleaned[] = $pkg;
                        }
                    }
                    if (isset($data["packages"])) {
                        $data["packages"] = $cleaned;
                    } else {
                        $data = $cleaned;
                    }
                    file_put_contents($installedJson, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            } catch (Throwable $e) {}
        }
        foreach (glob("$root/bootstrap/cache/*.php") as $file) {
            @unlink($file);
        }
    ' "$APP_DIR"

    print_success "Deployment cache and packages verified"
}

# â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
#  MAIN
# â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
main() {
    print_header "PropOS Platform — Shared Hosting Deploy"
    echo -e "  App dir    : ${BOLD}$APP_DIR${RESET}"
    echo -e "  Deploy mode: ${BOLD}$DEPLOY_MODE${RESET}"
    echo -e "  Date       : ${BOLD}$(date '+%Y-%m-%d %H:%M:%S')${RESET}"

    # Auto-detect first vs update
    if [[ "$DEPLOY_MODE" == "auto" ]]; then
        if [[ ! -f "$APP_DIR/.env" ]] || [[ ! -d "$APP_DIR/vendor" ]]; then
            DEPLOY_MODE="first-deploy"
        else
            DEPLOY_MODE="update"
        fi
        print_info "Auto-detected mode: $DEPLOY_MODE"
    fi

    # Run steps
    detect_php
    check_extensions
    detect_composer
    clean_deployment_cache

    if [[ "$DEPLOY_MODE" == "first-deploy" ]]; then
        setup_env
        install_deps
        build_assets
        run_migrations
        run_seeders
        setup_storage_link
        set_permissions
        optimize_app
        setup_public_dir
        setup_php_ini
        show_cron_instructions
        post_deploy_check

        print_header "First Deploy Complete"
        echo -e "  ${GREEN}Your application is deployed!${RESET}"
        echo -e "  ${YELLOW}Don't forget to:${RESET}"
        print_info "1. Verify APP_DEBUG=false and APP_ENV=production in .env"
        print_info "2. Add cron jobs shown above in cPanel"
        print_info "3. Configure HTTPS/SSL in cPanel (Let's Encrypt)"
        print_info "4. Test your site and all payment webhooks"

    else
        # Update deploy — put app in maintenance first
        enable_maintenance

        install_deps
        build_assets
        run_migrations
        setup_storage_link
        set_permissions
        optimize_app
        setup_public_dir
        setup_php_ini

        disable_maintenance
        post_deploy_check

        print_header "Update Deploy Complete"
        echo -e "  ${GREEN}Application updated successfully!${RESET}"
    fi
}

main "$@"
