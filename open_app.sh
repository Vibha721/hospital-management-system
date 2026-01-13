#!/bin/bash
# Open the app in your default browser, auto-detecting the running server port.

PROJECT_DIR="/Users/vibhagothe/Desktop/hospital:clinic management"
DEFAULT_PATH="index.html"
URL_PATH="${URL_PATH:-$DEFAULT_PATH}"

echo "🌐 Opening HealthCare Pro - Hospital Management..."

find_running_port() {
  for p in $(seq 8000 8010); do
    code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${p}/${URL_PATH}")
    if [ "$code" = "200" ] || [ "$code" = "302" ] || [ "$code" = "301" ]; then
      echo "$p"
      return 0
    fi
  done
  return 1
}

start_server_if_needed() {
  XAMPP_PHP="/Applications/XAMPP/xamppfiles/bin/php"
  if [ -n "$PHP_BIN" ] && [ -x "$PHP_BIN" ]; then
    PHP="$PHP_BIN"
  elif [ -x "$XAMPP_PHP" ]; then
    PHP="$XAMPP_PHP"
  elif command -v php >/dev/null 2>&1; then
    PHP="$(command -v php)"
  else
    echo "Error: PHP not found. Install XAMPP or add php to PATH."
    exit 1
  fi

  PORT="${PORT:-8000}"
  while lsof -i TCP:"$PORT" >/dev/null 2>&1; do
    PORT=$((PORT+1))
  done

  echo "Starting PHP server on http://localhost:$PORT" >&2
  (cd "$PROJECT_DIR" && "$PHP" -S "localhost:$PORT") >/dev/null 2>&1 &
  sleep 1
  echo "$PORT"
}

PORT="$(find_running_port)"
if [ -z "$PORT" ]; then
  PORT="$(start_server_if_needed)"
fi

URL="http://localhost:${PORT}/${URL_PATH}"
echo "Opening $URL"

if command -v open >/dev/null 2>&1; then
  open "$URL"
elif command -v xdg-open >/dev/null 2>&1; then
  xdg-open "$URL"
else
  echo "Please open this URL in your browser:"
  echo "$URL"
fi

exit 0
