<?php

/**
 * @file
 * Contains \Drupal\Tests\shortcut\Unit\Plugin\migrate\source\d7\ShortcutSetUsersTest.
 */

namespace Drupal\Tests\shortcut\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 ShortcutSetUsers source plugin.
 *
 * @group shortcut
 */
class ShortcutSetUsersTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\shortcut\Plugin\migrate\source\d7\ShortcutSetUsers';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_shortcut_set_users',
    ],
  ];

  protected $expectedResults = [
    [
      'uid' => '2',
      'set_name' => 'shortcut-set-2',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['shortcut_set_users'] = $this->expectedResults;
    parent::setUp();
  }

}
