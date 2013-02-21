<?php

namespace Drupal\system\Tests\PathProcessor;

use Drupal\Core\Database\Connection;
use Drupal\system\Tests\Path\UrlAliasFixtures;

/**
 * Utility methods to provide necessary database tables for tests.
 */
class PathProcessorFixtures extends UrlAliasFixtures {

  /**
   * Overrides Drupal\system\Tests\Path\UrlAliasFixtures::tableDefinition() .
   */
  public function tableDefinition() {
    // In addition to the tables added by the parent method, we also need the
    // language and variable tables for the path processor tests.
    $tables = parent::tableDefinition();
    $schema = system_schema();
    $tables['variable'] = $schema['variable'];
    module_load_install('language');
    $schema = language_schema();
    $tables['language'] = $schema['language'];
    return $tables;
  }
}
