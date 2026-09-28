#!/usr/bin/env bash
#
# Builds the HAMISTA release ZIPs into dist/.
#
#   bash tools/build-zips.sh        (or: npm run zip)
#
# Each package ZIP is named after the package's "Version:" header and has the package folder as its
# root, so it installs through Plugins/Themes -> Add New -> Upload. Packages that do not exist yet
# are skipped with a warning. Run "npm run build" first so the .min assets are current.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"
EXCLUDES=( '*/tests/*' '*.map' '*.DS_Store' '*/node_modules/*' '*/.git*' )

command -v zip >/dev/null 2>&1 || { echo "ERROR  the zip CLI is required" >&2; exit 1; }
mkdir -p "$DIST"

# Prints the first "Version:" header value of a theme style.css or plugin main file.
header_version() {
	sed -n -E 's/^[[:space:]*#@\/]*Version:[[:space:]]*([^[:space:]]+).*$/\1/p' "$1" | head -n 1
}

BUILT=()

# package_zip <parent dir> <folder> <header file> <zip base name>
package_zip() {
	local parent="$1" folder="$2" header="$3" name="$4" version zipfile
	if [[ ! -d "$parent/$folder" ]]; then
		echo "WARN   skip $name: $parent/$folder does not exist yet" >&2
		return 0
	fi
	if [[ ! -f "$parent/$folder/$header" ]]; then
		echo "WARN   skip $name: $folder/$header not found" >&2
		return 0
	fi
	version="$(header_version "$parent/$folder/$header")"
	if [[ -z "$version" ]]; then
		echo "ERROR  $folder/$header has no Version: header" >&2
		exit 1
	fi
	zipfile="$DIST/$name-$version.zip"
	rm -f "$zipfile"
	( cd "$parent" && zip -r -X -q "$zipfile" "$folder" -x "${EXCLUDES[@]}" )
	BUILT+=( "$zipfile" )
	echo "zip    dist/$(basename "$zipfile")"
}

package_zip "$ROOT/wp-content/themes" hamista style.css hamista-theme
for plugin in hamista-core hamista-license-manager hamista-customer-dashboard hamista-service-orders; do
	package_zip "$ROOT/wp-content/plugins" "$plugin" "$plugin.php" "$plugin"
done

# Platform version (package.json) names the bundles that are not a single package.
PLATFORM_VERSION="$(sed -n -E 's/^[[:space:]]*"version":[[:space:]]*"([^"]+)".*$/\1/p' "$ROOT/package.json" | head -n 1)"
PLATFORM_VERSION="${PLATFORM_VERSION:-1.0.0}"

if [[ -d "$ROOT/demo/elementor-templates" ]]; then
	STAGE="$DIST/elementor.tmp"
	rm -rf "$STAGE" && mkdir -p "$STAGE/hamista-elementor-templates"
	cp -R "$ROOT/demo/elementor-templates/." "$STAGE/hamista-elementor-templates/"
	zipfile="$DIST/hamista-elementor-templates-$PLATFORM_VERSION.zip"
	rm -f "$zipfile"
	( cd "$STAGE" && zip -r -X -q "$zipfile" hamista-elementor-templates -x "${EXCLUDES[@]}" )
	rm -rf "$STAGE"
	BUILT+=( "$zipfile" )
	echo "zip    dist/$(basename "$zipfile")"
else
	echo "WARN   skip hamista-elementor-templates: demo/elementor-templates/ does not exist yet" >&2
fi

if (( ${#BUILT[@]} == 0 )); then
	echo "ERROR  no package found; nothing to bundle" >&2
	exit 1
fi

# The complete bundle: every ZIP above plus the user documentation (internal planning docs excluded).
STAGE="$DIST/complete.tmp"
rm -rf "$STAGE" && mkdir -p "$STAGE/hamista-complete"
cp "${BUILT[@]}" "$STAGE/hamista-complete/"
if [[ -d "$ROOT/docs" ]]; then
	mkdir -p "$STAGE/hamista-complete/docs"
	( cd "$ROOT/docs" && tar --exclude='./superpowers' -cf - . ) | ( cd "$STAGE/hamista-complete/docs" && tar -xf - )
fi
zipfile="$DIST/hamista-complete-$PLATFORM_VERSION.zip"
rm -f "$zipfile"
( cd "$STAGE" && zip -r -X -q "$zipfile" hamista-complete -x "${EXCLUDES[@]}" )
rm -rf "$STAGE"
echo "zip    dist/$(basename "$zipfile")"
