<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeRevisionTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;

/**
 * Node content revisions migration.
 *
 * @group migrate_drupal
 */
class MigrateNodeRevisionTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $id_mappings = array(
      'd6_node' => array(
        array(array(1), array(1)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $dumps = array(
      $this->getDumpDirectory() . '/Users.php',
    );
    $this->loadDumps($dumps);

    // Create our users for the node authors.
    $query = Database::getConnection('default', 'migrate')->query('SELECT * FROM {users} WHERE uid NOT IN (0, 1)');
    while(($row = $query->fetchAssoc()) !== FALSE) {
      $user = entity_create('user', $row);
      $user->enforceIsNew();
      $user->save();
    }

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_node_revision');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test node revisions migration from Drupal 6 to 8.
   */
  public function testNodeRevision() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    /** @var \Drupal\node\NodeInterface $node */
    $this->assertIdentical($node->id(), '1');
    $this->assertIdentical($node->getRevisionId(), '2');
    $this->assertIdentical($node->langcode->value, 'und');
    $this->assertIdentical($node->getTitle(), 'Test title rev 2');
    $this->assertIdentical($node->body->value, 'body test rev 2');
    $this->assertIdentical($node->body->summary, 'teaser test rev 2');
    $this->assertIdentical($node->getRevisionAuthor()->id(), '2');
    $this->assertIdentical($node->revision_log->value, 'modified rev 2');
    $this->assertIdentical($node->getRevisionCreationTime(), '1390095702');

    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(5);
    $this->assertIdentical($node->id(), '1');
    $this->assertIdentical($node->body->value, 'body test rev 3');
    $this->assertIdentical($node->getRevisionAuthor()->id(), '1');
    $this->assertIdentical($node->revision_log->value, 'modified rev 3');
    $this->assertIdentical($node->getRevisionCreationTime(), '1390095703');
  }

}
