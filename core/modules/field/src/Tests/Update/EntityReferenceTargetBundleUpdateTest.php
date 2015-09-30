<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Update\EntityReferenceTargetBundleUpdateTest.
 */

namespace Drupal\field\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that field settings are properly updated during database updates.
 *
 * @group field
 */
class EntityReferenceTargetBundleUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests field_update_8001().
   *
   * @see field_update_8001()
   */
  public function testFieldUpdate8001() {
    $configFactory = $this->container->get('config.factory');

    // Load the 'node.field_image' field storage config, and check that is has
    // a 'target_bundle' setting.
    /** @var \Drupal\Core\Config\Config */
    $config = $configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertTrue(array_key_exists('target_bundle', $settings));

    // Run updates.
    $this->runUpdates();

    // Reload the config, and check that the 'target_bundle' setting has been
    // removed.
    $config = $configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertFalse(array_key_exists('target_bundle', $settings));
  }

}
