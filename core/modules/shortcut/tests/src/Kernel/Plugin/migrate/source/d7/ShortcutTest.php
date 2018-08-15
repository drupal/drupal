<?php

namespace Drupal\Tests\shortcut\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 Shortcut source plugin.
 *
 * @covers Drupal\shortcut\Plugin\migrate\source\d7\Shortcut
 *
 * @group shortcut
 */
class ShortcutTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['shortcut', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['menu_links'] = [
      [
        'menu_name' => 'shortcut-set-2',
        'mlid' => '473',
        'plid' => '0',
        'link_path' => 'admin/people',
        'router_path' => 'admin/people',
        'link_title' => 'People',
        'options' => 'a:0:{}',
        'module' => 'menu',
        'hidden' => '0',
        'external' => '0',
        'has_children' => '0',
        'expanded' => '0',
        'weight' => '-50',
        'depth' => '1',
        'customized' => '0',
        'p1' => '473',
        'p2' => '0',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'mlid' => '473',
        'menu_name' => 'shortcut-set-2',
        'link_path' => 'admin/people',
        'link_title' => 'People',
        'weight' => '-50',
      ],
    ];

    return $tests;
  }

}
