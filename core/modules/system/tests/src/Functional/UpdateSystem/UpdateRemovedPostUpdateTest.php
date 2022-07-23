<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests hook_removed_post_updates().
 *
 * @group Update
 */
class UpdateRemovedPostUpdateTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An user that can execute updates.
   *
   * @var \Drupal\Core\Url
   */
  protected $updateUrl;

  /**
   * An user that can execute updates.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $updateUser;

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

    $this->updateUrl = Url::fromRoute('system.db_update');
    $this->updateUser = $this->drupalCreateUser([
      'administer software updates',
    ]);
  }

  /**
   * Tests hook_post_update_NAME().
   */
  public function testRemovedPostUpdate() {
    // Mimic the behavior of ModuleInstaller::install().
    $key_value = \Drupal::service('keyvalue');
    $existing_updates = $key_value->get('post_update')->get('existing_updates', []);

    // Excludes 'update_test_postupdate_post_update_baz',
    // 'update_test_postupdate_post_update_bar', and
    // 'update_test_postupdate_pub' to simulate a module updating from
    // a version prior to the post-updates being added, to a version
    // after they were removed.
    $post_updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test0',
      'update_test_postupdate_post_update_foo',
    ];
    $key_value->get('post_update')->set('existing_updates', array_merge($existing_updates, $post_updates));

    // The message should inform us we've skipped two major versions.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Requirements problem');
    $assert_session->pageTextContains('The installed version of the Update test after module is too old to update. Update first to a version prior to all of the following: 8.x-2.0, 3.0.0');
    $assert_session->pageTextContains('update_test_postupdate_post_update_baz');
    $assert_session->pageTextContains('update_test_postupdate_post_update_bar');
    $assert_session->pageTextContains('update_test_postupdate_post_update_pub');

    // Excludes 'update_test_postupdate_post_update_baz' and
    // 'update_test_post_update_pub' to simulate two updates being
    // removed from a single version.
    $post_updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test0',
      'update_test_postupdate_post_update_foo',
      'update_test_postupdate_post_update_bar',
    ];
    $key_value->get('post_update')->set('existing_updates', array_merge($existing_updates, $post_updates));
    // Now the message should inform us we've skipped one version.
    $this->drupalGet($this->updateUrl);
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Requirements problem');
    $assert_session->pageTextContains('The installed version of the Update test after module is too old to update. Update to a version prior to 3.0.0');
    $assert_session->pageTextContains('update_test_postupdate_post_update_baz');
    $assert_session->pageTextContains('update_test_postupdate_post_update_pub');

    // Excludes 'update_test_postupdate_post_update_baz' to simulate
    // updating when only a single update has been skipped.
    $post_updates = [
      'update_test_postupdate_post_update_first',
      'update_test_postupdate_post_update_second',
      'update_test_postupdate_post_update_test1',
      'update_test_postupdate_post_update_test0',
      'update_test_postupdate_post_update_foo',
      'update_test_postupdate_post_update_bar',
      'update_test_postupdate_post_update_pub',
    ];
    $key_value->get('post_update')->set('existing_updates', array_merge($existing_updates, $post_updates));
    $this->drupalGet($this->updateUrl);
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Requirements problem');
    $assert_session->pageTextContains('The installed version of the Update test after module is too old to update. Update to a version prior to 3.0.0');
    $assert_session->pageTextContains('update_test_postupdate_post_update_baz');
  }

}
