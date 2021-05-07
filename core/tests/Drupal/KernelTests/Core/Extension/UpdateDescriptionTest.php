<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for update descriptions.
 *
 * @group Core
 */
class UpdateDescriptionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_description'];

  /**
   * Tests the list of pending database updates.
   *
   * @see update_get_update_list()
   */
  public function testUpdateGetUpdateList() {
    require_once $this->root . '/core/includes/update.inc';
    drupal_set_installed_schema_version('update_test_description', 8000);
    \Drupal::moduleHandler()->loadInclude('update_test_description', 'install');

    $updates = update_get_update_list();
    $expected = [
      'pending' => [
        8001 => '8001 - Update test of slash in description and/or.',
        8002 => '8002 - Update test with multiline description, the quick brown fox jumped over the lazy dog.',
      ],
      'start' => 8001,
    ];
    $this->assertEquals($expected, $updates['update_test_description']);
  }

}
