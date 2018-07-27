#!/bin/bash

VERSION=$1
SLUG="simple-page-sidebars"

if [[ "" = "$VERSION" ]]; then
	VERSION=$(sed -n "s/ \* Version:[ ]*\(.*\)/\1/p" ${SLUG}.php)
fi

mkdir -p "dist/${SLUG}"

rsync -av \
	--exclude .git \
	--exclude \.gitignore \
	--exclude bin \
	--exclude dist \
	--exclude svn \
	./ "dist/${SLUG}"

rm "dist/${SLUG}-${VERSION}.zip"
cd dist
zip -r "${SLUG}-${VERSION}.zip" "${SLUG}"
rm -rf "${SLUG}"
