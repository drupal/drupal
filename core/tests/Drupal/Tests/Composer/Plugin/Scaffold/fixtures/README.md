# Fixtures README

These fixtures are automatically copied to a temporary directory during test
runs. After the test run, the fixtures are automatically deleted.

Set the SCAFFOLD_FIXTURE_DIR environment variable to place the fixtures in a
specific location rather than a temporary directory. If this is done, then the
fixtures will not be deleted after the test run. This is useful for ad-hoc
testing.

Example:

$ SCAFFOLD_FIXTURE_DIR=$HOME/tmp/scaffold-fixtures composer unit
$ cd $HOME/tmp/scaffold-fixtures
$ cd drupal-drupal
$ composer drupal:scaffold

Scaffolding files for fixtures/drupal-assets-fixture:
  - Link [web-root]/.csslintrc from assets/.csslintrc
  - Link [web-root]/.editorconfig from assets/.editorconfig
  - Link [web-root]/.eslintignore from assets/.eslintignore
  - Link [web-root]/.eslintrc.json from assets/.eslintrc.json
  - Link [web-root]/.gitattributes from assets/.gitattributes
  - Link [web-root]/.ht.router.php from assets/.ht.router.php
  - Skip [web-root]/.htaccess: overridden in my/project
  - Link [web-root]/sites/default/default.services.yml from assets/default.services.yml
  - Skip [web-root]/sites/default/default.settings.php: overridden in fixtures/scaffold-override-fixture
  - Link [web-root]/sites/example.settings.local.php from assets/example.settings.local.php
  - Link [web-root]/sites/example.sites.php from assets/example.sites.php
  - Link [web-root]/index.php from assets/index.php
  - Skip [web-root]/robots.txt: overridden in my/project
  - Link [web-root]/update.php from assets/update.php
Scaffolding files for fixtures/scaffold-override-fixture:
  - Link [web-root]/sites/default/default.settings.php from assets/override-settings.php
Scaffolding files for my/project:
  - Skip [web-root]/.htaccess: disabled
  - Link [web-root]/robots.txt from assets/robots-default.txt
