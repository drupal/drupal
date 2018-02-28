<?php

namespace Drupal\Tests\field\Unit\Plugin\migrate\process;

use Drupal\field\Plugin\migrate\process\FieldTypeDefaults;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the deprecation of the field_type_defaults process plugin.
 *
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\FieldTypeDefaults
 * @group field
 * @group legacy
 */
class FieldTypeDefaultsTest extends MigrateProcessTestCase {

  /**
   * Tests that the field_type_defaults plugin triggers a deprecation error.
   *
   * @expectedDeprecation The field_type_defaults process plugin is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Use d6_field_type_defaults or d7_field_type_defaults instead. See https://www.drupal.org/node/2944589.
   */
  public function testDeprecatedError() {
    $this->plugin = new FieldTypeDefaults([], 'field_type_defaults', []);
  }

}
