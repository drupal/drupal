<?php

namespace Drupal\Tests\shortcut\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 ShortcutSet source plugin.
 *
 * @group shortcut
 */
class ShortcutSetTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\shortcut\Plugin\migrate\source\d7\ShortcutSet';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_shortcut_set',
    ],
  ];

  protected $expectedResults = [
    [
      'set_name' => 'shortcut-set-2',
      'title' => 'Alternative shortcut set',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['shortcut_set'] = $this->expectedResults;
    parent::setUp();
  }

}
