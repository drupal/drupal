<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests hook_post_update() when there is an exception in a post update.
 *
 * @group Update
 */
class UpdatePostUpdateExceptionTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $connection = Database::getConnection();

    // Set the schema version.
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('post_update_test_failing', 8000);

    // Update core.extension.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['post_update_test_failing'] = 8000;
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
    $this->assertSession()->pageTextContains('Failed: RuntimeException: This post update fails in post_update_test_failing_post_update_exception()');
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    $this->assertSession()->assertEscaped("Post update that throws an exception.");
  }

}
