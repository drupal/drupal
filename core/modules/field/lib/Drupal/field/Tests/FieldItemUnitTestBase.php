<?php

/**
 * @file
 * Contains \Drupal\field\Tests\FieldItemUnitTestBase.
 */

namespace Drupal\field\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Base test class for field type item tests.
 */
class FieldItemUnitTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'system', 'field', 'text', 'field_sql_storage', 'field_test', 'entity_test');

  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('field', 'field_config');
    $this->installSchema('field', 'field_config_instance');
    $this->installSchema('entity_test', 'entity_test');
  }

}
