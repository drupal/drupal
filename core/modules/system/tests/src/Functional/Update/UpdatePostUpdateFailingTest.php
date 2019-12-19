<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests hook_post_update() when there are failing update hooks.
 *
 * @group Update
 */
class UpdatePostUpdateFailingTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $connection = Database::getConnection();

    // Set the schema version.
    $connection->merge('key_value')
      ->condition('collection', 'system.schema')
      ->condition('name', 'update_test_failing')
      ->fields([
        'collection' => 'system.schema',
        'name' => 'update_test_failing',
        'value' => 'i:8000;',
      ])
      ->execute();

    // Update core.extension.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['update_test_failing'] = 8000;
    $connection->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }

  /**
   * Tests hook_post_update_NAME().
   */
  public function testPostUpdate() {
    // There are expected to be failed updates.
    $this->checkFailedUpdates = FALSE;

    $this->runUpdates();

    // There should be no post update hooks registered as being run.
    $this->assertIdentical([], \Drupal::state()->get('post_update_test_execution', []));

    $key_value = \Drupal::keyValue('update__post_update');
    $this->assertEqual([], $key_value->get('existing_updates', []));
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    // First update, should not be run since this module's update hooks fail.
    $this->assertRaw('8001 -   This update will fail.');
    $this->assertRaw('8002 -   A further update.');
    $this->assertEscaped("First update, should not be run since this module's update hooks fail.");
  }

}
