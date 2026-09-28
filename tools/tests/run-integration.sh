#!/usr/bin/env bash
# Runs HAMISTA integration tests inside the local WordPress through WP-CLI.
#
# Usage:
#   tools/tests/run-integration.sh            every wp-content/**/tests/integration/*.php
#   tools/tests/run-integration.sh <path>     one test file, or the integration tests under a directory
#
# Environment:
#   ENV               test environment (WordPress + WP-CLI wrapper at $ENV/wp);
#                     defaults to the session scratchpad environment.
#   HAMISTA_TEST_URL  base URL for hm_http() (default http://127.0.0.1:8080).
#
# Each file runs as: $ENV/wp eval-file <file> --use-include --require=tools/tests/wp-bootstrap.php
# The exit code is non-zero when any file fails.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV="${ENV:-/tmp/claude-0/-home-user-hamista/c5cfabc0-d2de-525b-9c2d-6b735d553134/scratchpad/env}"
BASE_URL="${HAMISTA_TEST_URL:-http://127.0.0.1:8080}"

if [ ! -x "$ENV/wp" ]; then
	echo "WP-CLI wrapper not found at $ENV/wp (set ENV to the test environment)." >&2
	exit 2
fi

TARGET="${1:-$ROOT/wp-content}"
if [ -f "$TARGET" ]; then
	files=("$(cd "$(dirname "$TARGET")" && pwd)/$(basename "$TARGET")")
elif [ -d "$TARGET" ]; then
	mapfile -t files < <(find "$TARGET" \( -name node_modules -o -name vendor \) -prune -o -path '*/tests/integration/*.php' -type f -print | sort)
else
	echo "No such file or directory: $TARGET" >&2
	exit 2
fi

if [ "${#files[@]}" -eq 0 ]; then
	echo "No integration tests found under $TARGET." >&2
	exit 1
fi

# HTTP tests need the local server; start it when it is down.
if ! curl -s -o /dev/null --max-time 5 "$BASE_URL/wp-login.php" && [ -x "$ENV/start-server.sh" ]; then
	"$ENV/start-server.sh" >/dev/null || { echo "Could not start the test server." >&2; exit 2; }
fi

status=0
for file in "${files[@]}"; do
	echo "== ${file#"$ROOT"/}"
	"$ENV/wp" eval-file "$file" --use-include --require="$ROOT/tools/tests/wp-bootstrap.php" || status=1
	echo
done

exit "$status"
