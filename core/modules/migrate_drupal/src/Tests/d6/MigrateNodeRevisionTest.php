<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeRevisionTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

/**
 * Test node revisions migration from Drupal 6 to 8.
 */
class MigrateNodeRevisionTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate nodes',
      'description'  => 'Node content revisions migration',
      'group' => 'Migrate Drupal',
    );
  }

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
    $this->prepareIdMappings($id_mappings);

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
    $this->assertEqual($node->id(), 1);
    $this->assertEqual($node->getRevisionId(), 2);
    $this->assertEqual($node->body->value, 'test rev 2');
  }

}
