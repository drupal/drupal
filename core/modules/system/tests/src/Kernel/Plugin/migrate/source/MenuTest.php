<?php

namespace Drupal\Tests\system\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests menu source plugin.
 *
 * @covers Drupal\system\Plugin\migrate\source\Menu
 *
 * @group system
 */
class MenuTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['menu_custom'] = [
      [
        'menu_name' => 'menu-name-1',
        'title' => 'menu custom value 1',
        'description' => 'menu custom description value 1',
      ],
      [
        'menu_name' => 'menu-name-2',
        'title' => 'menu custom value 2',
        'description' => 'menu custom description value 2',
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['menu_custom'];

    return $tests;
  }

}
