<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;

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
    $node = node_load(1);
    $this->assertEqual($node->id(), 1, 'Node 1 loaded.');
    $this->assertEqual($node->langcode->value, 'und');
    $this->assertEqual($node->body->value, 'test');
    $this->assertEqual($node->body->summary, 'test');
    $this->assertEqual($node->body->format, 'filtered_html');
    $this->assertEqual($node->getType(), 'story', 'Node has the correct bundle.');
    $this->assertEqual($node->getTitle(), 'Test title', 'Node has the correct title.');
    $this->assertEqual($node->getCreatedTime(), 1388271197, 'Node has the correct created time.');
    $this->assertEqual($node->isSticky(), FALSE);
    $this->assertEqual($node->getOwnerId(), 1);
    $this->assertEqual($node->getRevisionCreationTime(), 1390095701, 'Node has the correct revision timestamp.');

    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = \Drupal::entityManager()->getStorage('node')->loadRevision(1);
    $this->assertEqual($node_revision->getTitle(), 'Test title');
    $this->assertEqual($node_revision->getRevisionAuthor()->id(), 1, 'Node revision has the correct user');
    // This is empty on the first revision.
    $this->assertEqual($node_revision->revision_log->value, '');

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

    $node = node_load(1);
    $this->assertEqual($node->getTitle(), 'New node title');
    // Test a multi-column fields are correctly upgraded.
    $this->assertEqual($node->body->value, 'test');
    $this->assertEqual($node->body->format, 'full_html');

  }

}
