#!/usr/bin/env bash
# Goal: integrate origin while keeping LOCAL content on conflicts, handle unrelated histories, then push.
# This will always store the version from the local directory
# into the repository
# it checks the correct directory, uses the remote path and branch.
# Set the variables before copy-ing it in a directory.
set -euo pipefail

URL="https://github.com/Mondial-IT/bm_dashboard.git"
DIR="bm_dashboard"
BRANCH="dev-d9-conversion-to-d11"

# Require we're somewhere under .../git_root/...
if [[ "$PWD" != *"/git_root/"* ]]; then
  echo "Error: not under /git_root/ (PWD=$PWD)" >&2
  exit 1
fi

# Compute the absolute /git_root prefix from PWD.
ROOT="${PWD%%/git_root/*}/git_root"
SCRIPT="${ROOT}/.scripts/push-to-github.sh"

# Call the central script, passing through any extra args.
exec bash "$SCRIPT" --url "$URL" --dir "$DIR" --branch "$BRANCH" "$@"
