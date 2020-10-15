<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests config schema deprecation.
 *
 * @group config
 * @group legacy
 */
class ConfigSchemaDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_schema_deprecated_test',
  ];

  /**
   * Tests config schema deprecation.
   */
  public function testConfigSchemaDeprecation() {
    $this->expectDeprecation('The \'complex_structure_deprecated\' config schema is deprecated in drupal:9.1.0 and is removed from drupal 10.0.0. Use the \'complex_structure\' config schema instead. See http://drupal.org/node/the-change-notice-nid.');
    $config = $this->config('config_schema_deprecated_test.settings');
    $config
      ->set('complex_structure_deprecated.type', 'fruits')
      ->set('complex_structure_deprecated.products', ['apricot', 'apple'])
      ->save();
    $this->assertSame(['type' => 'fruits', 'products' => ['apricot', 'apple']], $config->get('complex_structure_deprecated'));
  }

}
