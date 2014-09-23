<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeRevisionTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

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
      $this->getDumpDirectory() . '/Drupal6NodeRevision.php',
    );
    $this->loadDumps($dumps);
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
    $this->assertEqual($node->id(), 1);
    $this->assertEqual($node->getRevisionId(), 2);
    $this->assertEqual($node->langcode->value, 'und');
    $this->assertEqual($node->getTitle(), 'Test title rev 2');
    $this->assertEqual($node->body->value, 'body test rev 2');
    $this->assertEqual($node->body->summary, 'teaser test rev 2');
    $this->assertEqual($node->getRevisionAuthor()->id(), 1);
    $this->assertEqual($node->revision_log->value, 'modified rev 2');
    $this->assertEqual($node->getRevisionCreationTime(), '1390095702');

    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(4);
    $this->assertEqual($node->id(), 1);
    $this->assertEqual($node->body->value, 'body test rev 3');
    $this->assertEqual($node->getRevisionAuthor()->id(), 1);
    $this->assertEqual($node->revision_log->value, 'modified rev 3');
    $this->assertEqual($node->getRevisionCreationTime(), '1390095703');
  }

}
