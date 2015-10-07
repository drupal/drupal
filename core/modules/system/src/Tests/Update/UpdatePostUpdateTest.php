<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePostUpdateTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests hook_post_update().
 *
 * @group Update
 */
class UpdatePostUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.update-test-postupdate-enabled.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    parent::doSelectionTest();

    // Ensure that normal and post_update updates are merged together on the
    // selection page.
    $this->assertRaw('<ul><li>8001 -   Normal update_N() function. </li><li>First update.</li><li>Second update.</li><li>Test1 update.</li><li>Test0 update.</li></ul>');
  }

  /**
   * Tests hook_post_update_NAME().
   */
  public function testPostUpdate() {
    $this->runUpdates();

    $this->assertRaw('<h3>Update first</h3>');
    $this->assertRaw('First update');
    $this->assertRaw('<h3>Update second</h3>');
    $this->assertRaw('Second update');
    $this->assertRaw('<h3>Update test1</h3>');
    $this->assertRaw('Test1 update');
    $this->assertRaw('<h3>Update test0</h3>');
    $this->assertRaw('Test0 update');

    $updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test0',
    ];
    $this->assertIdentical($updates, \Drupal::state()->get('post_update_test_execution', []));

    $key_value = \Drupal::keyValue('post_update');
    $updates = array_merge([
      'block_post_update_disable_blocks_with_missing_contexts',
      'field_post_update_save_custom_storage_property',
      'field_post_update_entity_reference_handler_setting',
      'system_post_update_recalculate_configuration_entity_dependencies',
      'views_post_update_update_cacheability_metadata',
    ], $updates);
    $this->assertEqual($updates, $key_value->get('existing_updates'));

    $this->drupalGet('update.php/selection');
    $this->assertText('No pending updates.');
  }

}
