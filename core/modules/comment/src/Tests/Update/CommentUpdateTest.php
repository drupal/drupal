<?php

namespace Drupal\comment\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that comment settings are properly updated during database updates.
 *
 * @group comment
 */
class CommentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8-rc1.filled.standard.php.gz',
    ];
  }

  /**
   * Tests comment_update_8200().
   *
   * @see comment_update_8200()
   */
  public function testCommentUpdate8101() {
    // Load the 'node.article.default' entity view display config, and check
    // that component 'comment' does not contain the 'view_mode' setting.
    $config = $this->config('core.entity_view_display.node.article.default');
    $this->assertNull($config->get('content.comment.settings.view_mode'));

    // Load the 'node.forum.default' entity view display config, and check that
    // component 'comment_forum' does not contain the 'view_mode' setting.
    $config = $this->config('core.entity_view_display.node.forum.default');
    $this->assertNull($config->get('content.comment_forum.settings.view_mode'));

    // Run updates.
    $this->runUpdates();

    // Check that 'node.article.default' entity view display setting 'view_mode'
    // has the value 'default'.
    $config = $this->config('core.entity_view_display.node.article.default');
    $this->assertIdentical($config->get('content.comment.settings.view_mode'), 'default');

    // Check that 'node.forum.default' entity view display setting 'view_mode'
    // has the value 'default'.
    $config = $this->config('core.entity_view_display.node.forum.default');
    $this->assertIdentical($config->get('content.comment_forum.settings.view_mode'), 'default');
  }

}
