<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Component\Render\FormattableMarkup;
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
  protected function setUp() {
    parent::setUp();
    $connection = Database::getConnection();

    // Set the schema version.
    $connection->merge('key_value')
      ->condition('collection', 'system.schema')
      ->condition('name', 'update_test_postupdate')
      ->fields([
        'collection' => 'system.schema',
        'name' => 'update_test_postupdate',
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
    $extensions['module']['update_test_postupdate'] = 8000;
    $connection->update('config')
      ->fields([
        'data' => serialize($extensions),
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    // Mimic the behaviour of ModuleInstaller::install() for removed post
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
    $this->assertRaw('<ul><li>8001 -   Normal update_N() function. </li><li>First update.</li><li>Second update.</li><li>Test0 update.</li><li>Test1 update.</li><li>Testing batch processing in post updates update.</li></ul>');
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
    $this->assertRaw('<h3>Update test_batch</h3>');
    $this->assertRaw('Test post update batches');

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
    $this->assertIdentical($updates, \Drupal::state()->get('post_update_test_execution', []));

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
      $this->assertEqual($existing_updates[$expected_update], 1, new FormattableMarkup("@expected_update exists in 'existing_updates' key and only appears once.", ['@expected_update' => $expected_update]));
    }

    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertText('No pending updates.');
  }

}
