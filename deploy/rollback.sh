#!/usr/bin/env bash
# =============================================================================
#  PropOS Platform — Rollback Script
#  Reverts the last deployment and re-enables the application.
#  Usage: bash deploy/rollback.sh
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"

artisan() { "$PHP_BIN" "$APP_DIR/artisan" "$@"; }

echo -e "${BOLD}${RED}PropOS Platform — Rollback${RESET}\n"
echo -e "${YELLOW}WARNING: This will roll back the last database migration batch.${RESET}"
echo -ne "Continue? (y/N): "
read -r confirm
[[ "$confirm" != "y" && "$confirm" != "Y" ]] && { echo "Aborted."; exit 0; }

echo -e "\n${CYAN}â–¶ Enabling maintenance mode...${RESET}"
artisan down --retry=30

echo -e "${CYAN}â–¶ Rolling back last migration batch...${RESET}"
artisan migrate:rollback --force
echo -e "${GREEN}âœ” Migrations rolled back${RESET}"

echo -e "${CYAN}â–¶ Clearing caches...${RESET}"
artisan config:clear
artisan route:clear
artisan view:clear
artisan cache:clear
echo -e "${GREEN}âœ” Caches cleared${RESET}"

echo -e "${CYAN}â–¶ Taking application back online...${RESET}"
artisan up
echo -e "${GREEN}âœ” Application online${RESET}"

echo -e "\n${YELLOW}Rollback complete. If you need to revert code, restore your previous"
echo -e "deployment archive or checkout the previous git tag manually.${RESET}"
