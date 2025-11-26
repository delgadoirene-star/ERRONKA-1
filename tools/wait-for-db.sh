#!/usr/bin/env bash
# Simple wait-for-db helper.
# Usage: wait-for-db.sh <cmd...>
# Waits for DB_HOST:DB_PORT (env) to accept TCP connections, then execs the given command.

set -e

HOST="${DB_HOST:-db}"
PORT="${DB_PORT:-3306}"
RETRIES="${WAIT_RETRIES:-60}"
SLEEP="${WAIT_SLEEP:-2}"

echo "wait-for-db: waiting for $HOST:$PORT (retries=$RETRIES, sleep=${SLEEP}s)..."

count=0
while true; do
    # Try to open TCP connection using bash /dev/tcp
    if timeout 1 bash -c "cat < /dev/tcp/$HOST/$PORT" >/dev/null 2>&1; then
        echo "wait-for-db: $HOST:$PORT is available"
        break
    fi

    count=$((count+1))
    if [ "$count" -ge "$RETRIES" ]; then
        echo "wait-for-db: timeout after $RETRIES attempts waiting for $HOST:$PORT" >&2
        exit 1
    fi
    sleep "$SLEEP"
done

# Exec the given command (if none provided, start apache)
if [ $# -gt 0 ]; then
    echo "wait-for-db: exec $*"
    exec "$@"
else
    echo "wait-for-db: no command provided; defaulting to apache2-foreground"
    exec apache2-foreground
fi
