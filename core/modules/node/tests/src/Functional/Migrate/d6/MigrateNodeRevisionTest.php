<?php

namespace Drupal\Tests\node\Functional\Migrate\d6;

use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;

/**
 * Node content revisions migration.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeRevisionTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'content_translation', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations(['d6_node', 'd6_node_revision']);
  }

  /**
   * Test node revisions migration from Drupal 6 to 8.
   */
  public function testNodeRevision() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    /** @var \Drupal\node\NodeInterface $node */
    $this->assertIdentical('1', $node->id());
    $this->assertIdentical('2', $node->getRevisionId());
    $this->assertIdentical('und', $node->langcode->value);
    $this->assertIdentical('Test title rev 2', $node->getTitle());
    $this->assertIdentical('body test rev 2', $node->body->value);
    $this->assertIdentical('teaser test rev 2', $node->body->summary);
    $this->assertIdentical('2', $node->getRevisionUser()->id());
    $this->assertIdentical('modified rev 2', $node->revision_log->value);
    $this->assertIdentical('1390095702', $node->getRevisionCreationTime());

    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(5);
    $this->assertIdentical('1', $node->id());
    $this->assertIdentical('body test rev 3', $node->body->value);
    $this->assertIdentical('1', $node->getRevisionUser()->id());
    $this->assertIdentical('modified rev 3', $node->revision_log->value);
    $this->assertIdentical('1390095703', $node->getRevisionCreationTime());
  }

}
