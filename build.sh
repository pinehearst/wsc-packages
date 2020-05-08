#!/usr/bin/env bash

set -e -u -o pipefail

PACKAGE="${1:?}"

SCRIPT_DIR="$(cd "${BASH_SOURCE[0]%/*}" && pwd)"
PACKAGE_DIR="${SCRIPT_DIR}/${PACKAGE}"

rm -f "${PACKAGE_DIR}".tar

RAW_PACKAGES=(
	language
)

for folder in "${RAW_PACKAGES[@]}"; do
	if ! [ -d "${PACKAGE_DIR}/${folder}" ]; then
		continue
	fi

	tar -r -f "${PACKAGE_DIR}".tar -C "${PACKAGE_DIR}" "${folder}"
done

TAR_PACKAGES=(
	files
	templates
	acptemplates
)

for folder in "${TAR_PACKAGES[@]}"; do
	rm -f "${PACKAGE_DIR}/${folder}".tar

	if ! [ -d "${PACKAGE_DIR}/${folder}" ]; then
		continue
	fi

	tar -c -f "${PACKAGE_DIR}/${folder}".tar -C "${PACKAGE_DIR}/${folder}" .
	tar -r -f "${PACKAGE_DIR}".tar -C "${PACKAGE_DIR}" "${folder}".tar
done

find "${PACKAGE_DIR}" -maxdepth 1 -name '*.xml' -print0 -o -name '*.sql' -print0 \
	| xargs -0 -n1 basename \
	| xargs tar -r -f "${PACKAGE_DIR}".tar -C "${PACKAGE_DIR}"
