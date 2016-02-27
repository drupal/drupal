<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d6\MigrateNodeTest.
 */

namespace Drupal\node\Tests\Migrate\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
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

    $node = Node::load(2);
    $this->assertIdentical('Test title rev 3', $node->getTitle());
    $this->assertIdentical('test rev 3', $node->body->value);
    $this->assertIdentical('filtered_html', $node->body->format);

    // Test that we can re-import using the EntityContentBase destination.
    $title = $this->rerunMigration();
    $node = Node::load(2);
    $this->assertIdentical($title, $node->getTitle());
    // Test multi-column fields are correctly upgraded.
    $this->assertIdentical('test rev 3', $node->body->value);
    $this->assertIdentical('full_html', $node->body->format);

    // Now insert a row indicating a failure and set to update later.
    $title = $this->rerunMigration(array(
      'sourceid1' => 2,
      'destid1' => NULL,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
    ));
    $node = Node::load(2);
    $this->assertIdentical($title, $node->getTitle());
  }

  /**
   * Execute the migration a second time.
   *
   * @param array $new_row
   *   An optional row to be inserted into the id map.
   *
   * @return string
   *   The new title in the source for vid 3.
   */
  protected function rerunMigration($new_row = []) {
    $title = $this->randomString();
    $migration = Migration::load('d6_node__story');
    $source_connection = Database::getConnection('default', 'migrate');
    $source_connection->update('node_revisions')
      ->fields(array(
        'title' => $title,
        'format' => 2,
      ))
      ->condition('vid', 3)
      ->execute();
    $table_name = $migration->getIdMap()->mapTableName();
    $default_connection = \Drupal::database();
    $default_connection->truncate($table_name)->execute();
    if ($new_row) {
      $hash = $migration->getIdMap()->getSourceIDsHash(['nid' => $new_row['sourceid1']]);
      $new_row['source_ids_hash'] = $hash;
      $default_connection->insert($table_name)
        ->fields($new_row)
        ->execute();
    }
    $this->executeMigration($migration);
    return $title;
  }

}
