#!/usr/bin/env bash
# =============================================================================
#  PropOS Platform — Interactive .env Setup Wizard
#  Run this ONCE on a fresh shared-hosting deployment to configure .env
#  Usage: bash deploy/setup-env.sh
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"

ask() {
    local prompt="$1" default="${2:-}" var_name="$3"
    local display_default=""
    [[ -n "$default" ]] && display_default=" [${YELLOW}${default}${RESET}]"
    echo -ne "${CYAN}${prompt}${display_default}: ${RESET}"
    read -r value
    [[ -z "$value" ]] && value="$default"
    printf -v "$var_name" '%s' "$value"
}

ask_secret() {
    local prompt="$1" var_name="$2"
    echo -ne "${CYAN}${prompt} (hidden): ${RESET}"
    read -rs value
    echo ""
    printf -v "$var_name" '%s' "$value"
}

echo -e "${BOLD}${CYAN}PropOS Platform — Environment Setup Wizard${RESET}\n"
echo -e "This wizard builds your ${BOLD}.env${RESET} file for shared hosting."
echo -e "Press ENTER to accept defaults shown in ${YELLOW}[brackets]${RESET}.\n"

# â”€â”€ App â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "${BOLD}â”€â”€ Application â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"
ask "App name"       "PropOS Platform"    APP_NAME
ask "App URL"        "https://"        APP_URL
ask "App environment (production/staging)" "production" APP_ENV
ask "Debug mode (true/false)" "false"  APP_DEBUG
ask "App locale"     "en"              APP_LOCALE

# â”€â”€ Database â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "\n${BOLD}â”€â”€ Database (MySQL) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"
ask "DB host"       "localhost"        DB_HOST
ask "DB port"       "3306"            DB_PORT
ask "DB name"       "PropOS_matchmaking"    DB_DATABASE
ask "DB username"   ""                DB_USERNAME
ask_secret "DB password"              DB_PASSWORD

# â”€â”€ Mail â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "\n${BOLD}â”€â”€ Mail â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"
echo -e "  ${YELLOW}Mailers: smtp | mailgun | ses | postmark | sendgrid${RESET}"
ask "Mail driver"       "smtp"                    MAIL_MAILER
ask "Mail host"         "mail.yourdomain.com"     MAIL_HOST
ask "Mail port"         "465"                     MAIL_PORT
ask "Mail encryption (ssl/tls)" "ssl"             MAIL_ENCRYPTION
ask "Mail username (your email)" ""               MAIL_USERNAME
ask_secret "Mail password"                        MAIL_PASSWORD
ask "From address"      "noreply@yourdomain.com"  MAIL_FROM
ask "From name"         "PropOS Platform"            MAIL_FROM_NAME

# â”€â”€ AI Provider â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "\n${BOLD}â”€â”€ AI Provider â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"
echo -e "  ${YELLOW}Providers: claude | openai | gemini | deepseek${RESET}"
ask "Default AI provider" "claude" AI_PROVIDER

ANTHROPIC_API_KEY=""; OPENAI_API_KEY=""; GEMINI_API_KEY=""; DEEPSEEK_API_KEY=""

if [[ "$AI_PROVIDER" == "claude" ]] || ask_yn "Add Anthropic/Claude key?"; then
    ask_secret "Anthropic API key" ANTHROPIC_API_KEY
fi
if [[ "$AI_PROVIDER" == "openai" ]] || ask_yn "Add OpenAI key?"; then
    ask_secret "OpenAI API key" OPENAI_API_KEY
fi

# â”€â”€ Payments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "\n${BOLD}â”€â”€ Payments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"
ask "Stripe publishable key" "pk_live_" STRIPE_PK
ask_secret "Stripe secret key"         STRIPE_SK
ask_secret "Stripe webhook secret"     STRIPE_WH
ask "Stripe currency"    "USD"         STRIPE_CURRENCY
ask "Paystack public key" "pk_live_"  PAYSTACK_PK
ask_secret "Paystack secret key"       PAYSTACK_SK
ask "Paystack currency"  "NGN"         PAYSTACK_CURRENCY

# â”€â”€ Write .env â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
echo -e "\n${BOLD}â”€â”€ Writing .env â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€${RESET}"

[[ -f "$ENV_FILE" ]] && cp "$ENV_FILE" "${ENV_FILE}.bak.$(date +%s)" && \
    echo -e "  Backed up existing .env"

cat > "$ENV_FILE" << EOF
APP_NAME="${APP_NAME}"
APP_ENV=${APP_ENV}
APP_KEY=
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

APP_LOCALE=${APP_LOCALE}
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD="${DB_PASSWORD}"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=${MAIL_MAILER}
MAIL_SCHEME=ssl
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME="${MAIL_USERNAME}"
MAIL_PASSWORD="${MAIL_PASSWORD}"
MAIL_FROM_ADDRESS="${MAIL_FROM}"
MAIL_FROM_NAME="${MAIL_FROM_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# AI Providers
AI_PROVIDER=${AI_PROVIDER}

ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
CLAUDE_MODEL=claude-sonnet-4-6

OPENAI_API_KEY=${OPENAI_API_KEY}
OPENAI_MODEL=gpt-4o

GEMINI_API_KEY=${GEMINI_API_KEY}
GEMINI_MODEL=gemini-2.0-flash

DEEPSEEK_API_KEY=${DEEPSEEK_API_KEY}
DEEPSEEK_MODEL=deepseek-chat

# Stripe
STRIPE_PUBLISHABLE_KEY=${STRIPE_PK}
STRIPE_SECRET_KEY=${STRIPE_SK}
STRIPE_WEBHOOK_SECRET=${STRIPE_WH}
STRIPE_CURRENCY=${STRIPE_CURRENCY}

# Paystack
PAYSTACK_PUBLIC_KEY=${PAYSTACK_PK}
PAYSTACK_SECRET_KEY=${PAYSTACK_SK}
PAYSTACK_CURRENCY=${PAYSTACK_CURRENCY}
EOF

echo -e "${GREEN}âœ” .env written to $ENV_FILE${RESET}"
echo -e "${YELLOW}  APP_KEY is blank — run deploy.sh to generate it.${RESET}"

ask_yn() {
    echo -ne "${CYAN}$1 (y/n) [n]${RESET}: "
    read -r yn
    [[ "$yn" == "y" || "$yn" == "Y" ]]
}
