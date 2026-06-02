#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="update_and_push_to_github.sh"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

SEARCH_DIR="$SCRIPT_DIR"
SCRIPTS_ROOT=""
while [[ "$SEARCH_DIR" != "/" ]]; do
  CANDIDATE="$SEARCH_DIR/.scripts"
  if [[ -f "$CANDIDATE/push-to-github.sh" && -f "$CANDIDATE/resolve_dir_paths.sh" && -f "$CANDIDATE/source_bm_colors.sh" ]]; then
    SCRIPTS_ROOT="$CANDIDATE"
    break
  fi
  SEARCH_DIR="$(dirname "$SEARCH_DIR")"
done

if [[ -z "$SCRIPTS_ROOT" ]]; then
  echo "Error: Unable to locate canonical .scripts helpers from ${SCRIPT_DIR}" >&2
  exit 1
fi

source "$SCRIPTS_ROOT/source_bm_colors.sh"
source "$SCRIPTS_ROOT/resolve_dir_paths.sh"

URL="https://github.com/Mondial-IT/bm_dashboard.git"
DIR="bm_dashboard"
BRANCH="dev-d9-conversion-to-d11"

MODE="update"
COMMIT_MESSAGE=""
case "${1:-}" in
  --status|-S|status|display)
    MODE="status"
    shift
    ;;
  --message|-m)
    shift
    COMMIT_MESSAGE="${*:-}"
    set --
    ;;
  *)
    COMMIT_MESSAGE="${*:-}"
    set --
    ;;
esac

SCRIPT="$SCRIPTS_ROOT/push-to-github.sh"
if [[ "$MODE" == "status" ]]; then
  echo -e "$Blue - Displaying git status for ${DIR} via ${SCRIPT}$NC"
  exec bash "$SCRIPT"     --url "$URL"     --dir "$DIR"     --branch "$BRANCH"     --worktree "$SCRIPT_DIR"     --status
fi

echo -e "$Blue - Running ${SCRIPT_NAME} for ${DIR} via ${SCRIPT}$NC"
exec bash "$SCRIPT"   --url "$URL"   --dir "$DIR"   --branch "$BRANCH"   --worktree "$SCRIPT_DIR"   --message "$COMMIT_MESSAGE"
