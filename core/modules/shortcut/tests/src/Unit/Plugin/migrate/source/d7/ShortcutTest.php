<?php

/**
 * @file
 * Contains \Drupal\Tests\shortcut\Unit\Plugin\migrate\source\d7\ShortcutTest.
 */

namespace Drupal\Tests\shortcut\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 Shortcut source plugin.
 *
 * @group shortcut
 */
class ShortcutTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\shortcut\Plugin\migrate\source\d7\Shortcut';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_shortcut',
    ],
  ];

  protected $expectedResults = [
    [
      'mlid' => '473',
      'menu_name' => 'shortcut-set-2',
      'link_path' => 'admin/people',
      'link_title' => 'People',
      'weight' => '-50',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['menu_links'][] = [
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
    ];

    parent::setUp();
  }

}
