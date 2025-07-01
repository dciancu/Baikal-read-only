.PHONY: dist clean

BUILD_DIR="build/baikal"

BUILD_FILES=Core html LICENSE README.md composer.json vendor composer.lock

VERSION=$(shell php -r "include 'Core/Distrib.php'; echo BAIKAL_VERSION;")

dist:
	# Building Baikal $(VERSION)
	composer update --no-interaction --no-dev
	rm -rf build
	mkdir -p $(BUILD_DIR) $(BUILD_DIR)/Specific $(BUILD_DIR)/Specific/db $(BUILD_DIR)/config
	touch $(BUILD_DIR)/Specific/db/.empty
	touch $(BUILD_DIR)/config/.empty
	rsync -av \
		$(BUILD_FILES) \
		--exclude="*.swp" \
		$(BUILD_DIR)
	composer config -d $(BUILD_DIR) platform.php 8.4
	composer update -d $(BUILD_DIR) --no-interaction --no-dev
	rm -r vendor
	cd build; mv baikal baikal-$(VERSION); zip -r baikal-$(VERSION).zip baikal-$(VERSION)/; rm -r baikal-$(VERSION); unzip baikal-$(VERSION).zip; rm baikal-$(VERSION).zip

clean:
	# Wipe out all local data, and go back to a clean install
	rm config/baikal.yaml Specific/db/db.sqlite; true
