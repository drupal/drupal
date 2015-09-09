<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePostUpdateFailingTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests hook_post_update() when there are failing update hooks.
 *
 * @group Update
 */
class UpdatePostUpdateFailingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.update-test-postupdate-failing-enabled.php',
    ];
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
    $this->assertEqual([], $key_value->get('existing_updates'));
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
