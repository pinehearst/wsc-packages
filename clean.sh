#!/usr/bin/env bash

set -e -u -o pipefail

SCRIPT_DIR="$(cd "${BASH_SOURCE[0]%/*}" && pwd)"

find "${SCRIPT_DIR}" -name '*.tar' -print -delete
