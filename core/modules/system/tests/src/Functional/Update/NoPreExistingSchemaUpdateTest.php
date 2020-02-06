<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tries to update a module which has no pre-existing schema.
 *
 * @group Update
 * @group legacy
 */
class NoPreExistingSchemaUpdateTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  protected function setUp() {
    parent::setUp();
    $connection = Database::getConnection();

    // Enable the update_test_no_preexisting module by altering the
    // core.extension configuration directly, so that the schema version
    // information is missing.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $connection->update('config')
      ->fields([
        'data' => serialize(array_merge_recursive($extensions, ['module' => ['update_test_no_preexisting' => 0]])),
      ])
      ->condition('name', 'core.extension')
      ->execute();
  }

  /**
   * Test the system module updates with no dependencies installed.
   */
  public function testNoPreExistingSchema() {
    $schema = \Drupal::keyValue('system.schema')->getAll();
    $this->assertArrayNotHasKey('update_test_no_preexisting', $schema);
    $this->assertFalse(\Drupal::state()->get('update_test_no_preexisting_update_8001', FALSE));

    $this->runUpdates();

    $schema = \Drupal::keyValue('system.schema')->getAll();
    $this->assertArrayHasKey('update_test_no_preexisting', $schema);
    $this->assertEquals('8001', $schema['update_test_no_preexisting']);
    $this->assertTrue(\Drupal::state()->get('update_test_no_preexisting_update_8001', FALSE));
  }

}
