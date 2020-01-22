<?php

namespace Drupal\Tests\comment\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that comment admin view is enabled after update.
 *
 * @see comment_post_update_enable_comment_admin_view()
 *
 * @group Update
 * @group legacy
 */
class CommentAdminViewUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'views'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that comment admin view is enabled after update.
   */
  public function testCommentAdminPostUpdateHook() {
    $this->runUpdates();
    // Ensure we can load the view from the storage after the update and it's
    // enabled.
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\views\ViewEntityInterface $comment_admin_view */
    $comment_admin_view = $entity_type_manager->getStorage('view')->load('comment');
    $this->assertNotNull($comment_admin_view, 'Comment admin view exist in storage.');
    $this->assertTrue((bool) $comment_admin_view->enable()->get('status'), 'Comment admin view is enabled.');
    $comment_delete_action = $entity_type_manager->getStorage('action')->load('comment_delete_action');
    $this->assertNotNull($comment_delete_action, 'Comment delete action imported');
    // Verify comment admin page is working after updates.
    $account = $this->drupalCreateUser(['administer comments']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/content/comment');
    $this->assertText(t('No comments available.'));
  }

}
