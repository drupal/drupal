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

    $tests[1] = $tests[0];
    $tests[1]['source_data']['menu_custom'][0] +=
      [
        'language' => 'it',
        'i18n_mode' => 1,
      ];
    $tests[1]['source_data']['menu_custom'][1] +=
      [
        'language' => 'fr',
        'i18n_mode' => 2,
      ];
    $tests[1]['expected_data'] = $tests[1]['source_data']['menu_custom'];

    return $tests;
  }

}
