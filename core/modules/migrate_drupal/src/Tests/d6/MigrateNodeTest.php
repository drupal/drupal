<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Node content migration.
 *
 * @group migrate_drupal
 */
class MigrateNodeTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_node');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    // This is required for the second import below.
    db_truncate($migration->getIdMap()->mapTableName())->execute();
    $this->standalone = TRUE;
  }

  /**
   * Test node migration from Drupal 6 to 8.
   */
  public function testNode() {
    $node = Node::load(1);
    $this->assertIdentical($node->id(), '1', 'Node 1 loaded.');
    $this->assertIdentical($node->langcode->value, 'und');
    $this->assertIdentical($node->body->value, 'test');
    $this->assertIdentical($node->body->summary, 'test');
    $this->assertIdentical($node->body->format, 'filtered_html');
    $this->assertIdentical($node->getType(), 'story', 'Node has the correct bundle.');
    $this->assertIdentical($node->getTitle(), 'Test title', 'Node has the correct title.');
    $this->assertIdentical($node->getCreatedTime(), '1388271197', 'Node has the correct created time.');
    $this->assertIdentical($node->isSticky(), FALSE);
    $this->assertIdentical($node->getOwnerId(), '1');
    $this->assertIdentical($node->getRevisionCreationTime(), '1420861423');

    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = \Drupal::entityManager()->getStorage('node')->loadRevision(1);
    $this->assertIdentical($node_revision->getTitle(), 'Test title');
    $this->assertIdentical($node_revision->getRevisionAuthor()->id(), '1', 'Node revision has the correct user');
    // This is empty on the first revision.
    $this->assertIdentical($node_revision->revision_log->value, '');

    // It is pointless to run the second half from MigrateDrupal6Test.
    if (empty($this->standalone)) {
      return;
    }

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

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_node');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $node = Node::load(1);
    $this->assertIdentical($node->getTitle(), 'New node title');
    // Test a multi-column fields are correctly upgraded.
    $this->assertIdentical($node->body->value, 'test');
    $this->assertIdentical($node->body->format, 'full_html');

    $node = Node::load(3);
    // Test that format = 0 from source maps to filtered_html.
    $this->assertIdentical($node->body->format, 'filtered_html');
  }

}
