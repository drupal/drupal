<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests hook_post_update().
 *
 * @group Update
 */
class UpdatePostUpdateTest extends BrowserTestBase {
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
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('update_test_postupdate', 8000);

    // Update core.extension.
    $extensions = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $extensions = unserialize($extensions);
    $extensions['module']['update_test_postupdate'] = 8000;
    $connection->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Mimic the behavior of ModuleInstaller::install() for removed post
    // updates. Don't include the actual post updates because we want them to
    // run.
    $key_value = \Drupal::service('keyvalue');
    $existing_updates = $key_value->get('post_update')->get('existing_updates', []);
    $post_updates = [
      'update_test_postupdate_post_update_foo',
      'update_test_postupdate_post_update_bar',
      'update_test_postupdate_post_update_pub',
      'update_test_postupdate_post_update_baz',
    ];
    $key_value->get('post_update')->set('existing_updates', array_merge($existing_updates, $post_updates));
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    // Ensure that normal and post_update updates are merged together on the
    // selection page.
    $this->assertSession()->responseContains('<ul><li>8001 - Normal update_N() function.</li><li>First update.</li><li>Second update.</li><li>Test0 update.</li><li>Test1 update.</li><li>Testing batch processing in post updates update.</li></ul>');
  }

  /**
   * Tests hook_post_update_NAME().
   */
  public function testPostUpdate() {
    $this->runUpdates();

    $this->assertSession()->responseContains('<h3>Update first</h3>');
    $this->assertSession()->pageTextContains('First update');
    $this->assertSession()->responseContains('<h3>Update second</h3>');
    $this->assertSession()->pageTextContains('Second update');
    $this->assertSession()->responseContains('<h3>Update test1</h3>');
    $this->assertSession()->pageTextContains('Test1 update');
    $this->assertSession()->responseContains('<h3>Update test0</h3>');
    $this->assertSession()->pageTextContains('Test0 update');
    $this->assertSession()->responseContains('<h3>Update test_batch</h3>');
    $this->assertSession()->pageTextContains('Test post update batches');

    // Test state value set by each post update.
    $updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test0',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test_batch-1',
      'update_test_postupdate_post_update_test_batch-2',
      'update_test_postupdate_post_update_test_batch-3',
    ];
    $this->assertSame($updates, \Drupal::state()->get('post_update_test_execution', []));

    // Test post_update key value stores contains a list of the update functions
    // that have run.
    $existing_updates = array_count_values(\Drupal::keyValue('post_update')->get('existing_updates'));
    $expected_updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test0',
      'update_test_postupdate_post_update_test_batch',
    ];
    foreach ($expected_updates as $expected_update) {
      $this->assertEquals(1, $existing_updates[$expected_update], "$expected_update exists in 'existing_updates' key and only appears once.");
    }

    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');
  }

}
