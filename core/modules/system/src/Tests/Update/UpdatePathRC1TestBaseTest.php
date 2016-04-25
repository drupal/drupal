<?php

namespace Drupal\system\Tests\Update;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the update path base class with the RC1 database dump.
 *
 * @group Update
 */
class UpdatePathRC1TestBaseTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the database was properly loaded.
   */
  public function testDatabaseLoaded() {
    $extensions = \Drupal::service('config.storage')->read('core.extension');
    $this->assertFalse(isset($extensions['theme']['stable']), 'Stable is not installed before updating.');
    $hook_updates = [
      'user' => '8000',
      'node' => '8003',
      'system' => '8013',
    ];
    foreach ($hook_updates as $module => $schema) {
      $this->assertEqual(drupal_get_installed_schema_version($module), $schema, new FormattableMarkup('Module @module schema is @schema', ['@module' => $module, '@schema' => $schema]));
    }

    // Test post_update key value stores contains a list of the update functions
    // that have run.
    $existing_updates = array_count_values(\Drupal::keyValue('post_update')->get('existing_updates'));
    $expected_updates = [
      'system_post_update_recalculate_configuration_entity_dependencies',
      'field_post_update_save_custom_storage_property',
      'field_post_update_entity_reference_handler_setting',
      'block_post_update_disable_blocks_with_missing_contexts',
      'views_post_update_update_cacheability_metadata',
    ];
    foreach ($expected_updates as $expected_update) {
      $this->assertEqual($existing_updates[$expected_update], 1, new FormattableMarkup("@expected_update exists in 'existing_updates' key and only appears once.", ['@expected_update' => $expected_update]));
    }

    $this->runUpdates();
    $this->assertEqual(\Drupal::config('system.site')->get('name'), 'Site-Install');
    $this->drupalGet('<front>');
    $this->assertText('Site-Install');
    $extensions = \Drupal::service('config.storage')->read('core.extension');
    $this->assertTrue(isset($extensions['theme']['stable']), 'Stable is installed after updating.');
    $blocks = \Drupal::entityManager()->getStorage('block')->loadByProperties(['theme' => 'stable']);
    $this->assertTrue(empty($blocks), 'No blocks have been placed for Stable.');
  }

}
