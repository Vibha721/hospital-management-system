#!/bin/bash
# Start PHP server using XAMPP's PHP

XAMPP_PHP="/Applications/XAMPP/xamppfiles/bin/php"
PROJECT_DIR="/Users/vibhagothe/Desktop/hospital:clinic management"

if [ -n "$PHP_BIN" ] && [ -x "$PHP_BIN" ]; then
    PHP="$PHP_BIN"
elif [ -f "$XAMPP_PHP" ]; then
    PHP="$XAMPP_PHP"
elif command -v php >/dev/null 2>&1; then
    PHP="$(command -v php)"
else
    echo "Error: PHP not found. Install XAMPP or add php to PATH."
    exit 1
fi

PORT="${PORT:-8000}"
if lsof -i TCP:"$PORT" >/dev/null 2>&1; then
    for p in $(seq $((PORT+1)) $((PORT+10))); do
        if ! lsof -i TCP:"$p" >/dev/null 2>&1; then
            PORT="$p"
            break
        fi
    done
fi

echo "Starting PHP server on http://localhost:$PORT"
echo "Press Ctrl+C to stop"
cd "$PROJECT_DIR" || exit 1
"$PHP" -S "localhost:$PORT"
