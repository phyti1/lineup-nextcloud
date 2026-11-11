# SPDX-FileCopyrightText: 2026 Pascal Berger
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# `make appstore` builds build/artifacts/lineup.tar.gz with a single top-level
# folder `lineup/` that contains only runtime files. The release workflow
# (.github/workflows/appstore-build-publish.yml) calls this target, signs the
# extracted folder and re-packs it.

app_name := lineup
build_dir := $(CURDIR)/build
artifacts_dir := $(build_dir)/artifacts
app_dir := $(artifacts_dir)/$(app_name)
runtime_files := appinfo lib templates js css img l10n COPYING CHANGELOG.md README.md

.PHONY: appstore clean lint

appstore: clean
	mkdir -p $(app_dir)
	cp -r $(runtime_files) $(app_dir)/
	tar -czf $(artifacts_dir)/$(app_name).tar.gz -C $(artifacts_dir) $(app_name)

clean:
	rm -rf $(build_dir)

lint:
	find appinfo lib templates -name '*.php' -print0 | xargs -0 -n1 php -l
	mkdir -p $(build_dir)
	curl -sSfL -o $(build_dir)/info.xsd https://apps.nextcloud.com/schema/apps/info.xsd
	xmllint --noout --schema $(build_dir)/info.xsd appinfo/info.xml
