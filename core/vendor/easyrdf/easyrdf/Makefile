PACKAGE = easyrdf
VERSION = $(shell php -r "print json_decode(file_get_contents('composer.json'))->version;")
distdir = $(PACKAGE)-$(VERSION)
PHP = $(shell which php)
COMPOSER_FLAGS=--no-ansi --no-interaction
PHPUNIT = vendor/bin/phpunit 
PHPUNIT_FLAGS = -c config/phpunit.xml
PHPCS = vendor/bin/phpcs
PHPCS_FLAGS = --standard=./config/phpcs_ruleset.xml --encoding=utf8 --extensions=php
SAMI = vendor/bin/sami.php

EXAMPLE_FILES = examples/*.php
SOURCE_FILES = lib/EasyRdf.php \
               lib/EasyRdf/*.php \
               lib/EasyRdf/*/*.php
TEST_FILES = test/*/*Test.php \
             test/*/*/*Test.php
TEST_SUPPORT = Makefile test/cli_example_wrapper.php \
               test/TestHelper.php \
               test/EasyRdf/TestCase.php \
               test/EasyRdf/Http/MockClient.php \
               test/EasyRdf/Serialiser/NtriplesArray.php \
               test/fixtures/*
DOC_FILES = composer.json \
            doap.rdf \
            docs/api \
            README.md \
            LICENSE.md \
            CHANGELOG.md

DISTFILES = $(EXAMPLE_FILES) $(SOURCE_FILES) $(TEST_FILES) \
            $(TEST_SUPPORT) $(DOC_FILES)

.DEFAULT: help
all: help

# TARGET:test                Run all the PHPUnit tests
.PHONY: test
test: $(PHPUNIT)
	mkdir -p reports
	$(PHP) $(PHPUNIT) $(PHPUNIT_FLAGS)

# TARGET:test-examples       Run PHPUnit tests for each of the examples
.PHONY: test-examples
test-examples: $(PHPUNIT)
	mkdir -p reports
	$(PHP) $(PHPUNIT) $(PHPUNIT_FLAGS) --testsuite "EasyRdf Examples"

# TARGET:test-lib            Run PHPUnit tests for the library
.PHONY: test-lib
test-lib: $(PHPUNIT)
	mkdir -p reports
	$(PHP) $(PHPUNIT) $(PHPUNIT_FLAGS) --testsuite "EasyRdf Library"

# TARGET:coverage            Run the library tests and generate a code coverage report
.PHONY: coverage
coverage: $(PHPUNIT)
	mkdir -p reports/coverage
	$(PHP) $(PHPUNIT) $(PHPUNIT_FLAGS) --coverage-html ./reports/coverage --testsuite "EasyRdf Library"

# TARGET:apidocs             Generate HTML API documentation
.PHONY: apidocs
apidocs: $(SAMI)
	$(PHP) $(SAMI) update config/sami.php -n -v --force

docs/api: apidocs

doap.rdf: doap.php composer.json
	$(PHP) doap.php > doap.rdf

# TARGET:cs                  Check the code style of the PHP source code
.PHONY: cs
cs: $(PHPCS)
	$(PHPCS) $(PHPCS_FLAGS) lib test

# TARGET:lint                Perform basic PHP syntax check on all files
.PHONY: lint
lint: $(EXAMPLE_FILES) $(SOURCE_FILES) $(TEST_FILES)
	@for file in $^; do  \
	  $(PHP) -l $$file || exit -1; \
	done

# TARGET:dist                Build tarball for distribution
.PHONY: dist
dist: $(distdir)
	tar zcf $(distdir).tar.gz $(distdir)
	rm -Rf $(distdir)
	@echo "Created $(distdir).tar.gz"

$(distdir): $(DISTFILES)
	@for file in $^; do  \
		dir=$(distdir)/`dirname "$$file"`; \
		test -d "$$dir" || mkdir -p "$$dir" || exit -1; \
		cp -Rfp "$$file" "$(distdir)/$$file" || exit -1; \
	done

# TARGET:clean               Delete any temporary and generated files
.PHONY: clean
clean:
	-rm -Rf $(distdir) reports vendor
	-rm -Rf docs/api samicache
	-rm -f composer.phar composer.lock
	-rm -f doap.rdf

# TARGET:check-fixme         Scan for files containing the words TODO or FIXME
.PHONY: check-fixme
check-fixme:
	@git grep -n -E 'FIXME|TODO' || echo "No FIXME or TODO lines found."

# TARGET:help                You're looking at it!
.PHONY: help
help:
	# Usage:
	#   make <target> [OPTION=value]
	#
	# Targets:
	@egrep "^# TARGET:" [Mm]akefile | sed 's/^# TARGET:/#   /'
	#
	# Options:
	#   PHP                 Path to php



# Composer rules
composer.phar:
	curl -s -z composer.phar -o composer.phar http://getcomposer.org/composer.phar

composer-install: composer.phar
	$(PHP) composer.phar $(COMPOSER_FLAGS) install --dev

composer-update: clean composer.phar
	$(PHP) composer.phar $(COMPOSER_FLAGS) update --dev

vendor/bin/phpunit: composer-install
vendor/bin/phpcs: composer-install
vendor/bin/sami.php: composer-install
