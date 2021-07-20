<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Functional\Update;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests Comment module update paths.
 *
 * @group comment
 * @group legacy
 */
class CommentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests comment_update_9200().
   *
   * @see comment_update_9200()
   */
  public function testCommentUpdate9100(): void {
    $config_factory = \Drupal::configFactory();

    $config = $config_factory->get('field.field.node.article.comment');
    $this->assertArrayNotHasKey('thread_limit', $config->get('settings'));

    $this->runUpdates();

    $config = $config_factory->get('field.field.node.article.comment');
    $this->assertSame(2, $config->get('settings.thread_limit.depth'));
    $this->assertSame(CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW, $config->get('settings.thread_limit.mode'));
  }

}
