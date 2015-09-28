<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Node content migration.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations(['d6_node:*']);

    // This is required for the second import below.
    \Drupal::database()->truncate(Migration::load('d6_node__story')->getIdMap()->mapTableName())->execute();
  }

  /**
   * Test node migration from Drupal 6 to 8.
   */
  public function testNode() {
    $node = Node::load(1);
    $this->assertIdentical('1', $node->id(), 'Node 1 loaded.');
    $this->assertIdentical('und', $node->langcode->value);
    $this->assertIdentical('test', $node->body->value);
    $this->assertIdentical('test', $node->body->summary);
    $this->assertIdentical('filtered_html', $node->body->format);
    $this->assertIdentical('story', $node->getType(), 'Node has the correct bundle.');
    $this->assertIdentical('Test title', $node->getTitle(), 'Node has the correct title.');
    $this->assertIdentical('1388271197', $node->getCreatedTime(), 'Node has the correct created time.');
    $this->assertIdentical(FALSE, $node->isSticky());
    $this->assertIdentical('1', $node->getOwnerId());
    $this->assertIdentical('1420861423', $node->getRevisionCreationTime());

    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = \Drupal::entityManager()->getStorage('node')->loadRevision(1);
    $this->assertIdentical('Test title', $node_revision->getTitle());
    $this->assertIdentical('1', $node_revision->getRevisionAuthor()->id(), 'Node revision has the correct user');
    // This is empty on the first revision.
    $this->assertIdentical(NULL, $node_revision->revision_log->value);

    // Test that we can re-import using the EntityContentBase destination.
    $connection = Database::getConnection('default', 'migrate');
    $connection->update('node_revisions')
      ->fields(array(
        'title' => 'New node title',
        'format' => 2,
      ))
      ->condition('vid', 1)
      ->execute();
    $connection->delete('content_field_test_two')
      ->condition('delta', 1)
      ->execute();

    $migration = Migration::load('d6_node__story');
    $this->executeMigration($migration);

    $node = Node::load(1);
    $this->assertIdentical('New node title', $node->getTitle());
    // Test a multi-column fields are correctly upgraded.
    $this->assertIdentical('test', $node->body->value);
    $this->assertIdentical('full_html', $node->body->format);
  }

}
