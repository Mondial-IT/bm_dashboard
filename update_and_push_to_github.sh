#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="update_and_push_to_github.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT="${SCRIPT_DIR}/../../../../../.scripts/push-to-github.sh"

URL="https://github.com/Mondial-IT/bm_dashboard.git"
DIR="bm_dashboard"
BRANCH="dev-d9-conversion-to-d11"

COMMIT_MESSAGE="${1:-}"
shift || true

# Call the central script, passing the message explicitly and any remaining args.
echo "Running ${SCRIPT_NAME} via ${SCRIPT}"
bash "${SCRIPT}" \
  --url "$URL" \
  --dir "$DIR" \
  --branch "$BRANCH" \
  --message "$COMMIT_MESSAGE" \
  "$@"
