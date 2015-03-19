<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBookTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Upgrade book structure.
 *
 * @group migrate_drupal
 */
class MigrateBookTest extends MigrateDrupal6TestBase {

  public static $modules = array('book');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $id_mappings = array();
    for ($i = 4; $i <= 8; $i++) {
      $entity = entity_create('node', array(
        'type' => 'story',
        'title' => "Node $i",
        'nid' => $i,
        'status' => TRUE,
      ));
      $entity->enforceIsNew();
      $entity->save();
      $id_mappings['d6_node'][] = array(array($i), array($i));
    }
    $this->prepareMigrations($id_mappings);
    // Load database dumps to provide source data.
    $dumps = array(
      $this->getDumpDirectory() . '/Book.php',
      $this->getDumpDirectory() . '/MenuLinks.php',
    );
    $this->loadDumps($dumps);
    // Migrate books..
    $migration = entity_load('migration', 'd6_book');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 book structure to Drupal 8 migration.
   */
  public function testBook() {
    $nodes = Node::loadMultiple(array(4, 5, 6, 7, 8));
    $this->assertIdentical('4', $nodes[4]->book['bid']);
    $this->assertIdentical('0', $nodes[4]->book['pid']);

    $this->assertIdentical('4', $nodes[5]->book['bid']);
    $this->assertIdentical('4', $nodes[5]->book['pid']);

    $this->assertIdentical('4', $nodes[6]->book['bid']);
    $this->assertIdentical('5', $nodes[6]->book['pid']);

    $this->assertIdentical('4', $nodes[7]->book['bid']);
    $this->assertIdentical('5', $nodes[7]->book['pid']);

    $this->assertIdentical('8', $nodes[8]->book['bid']);
    $this->assertIdentical('0', $nodes[8]->book['pid']);

    $tree = \Drupal::service('book.manager')->bookTreeAllData(4);
    $this->assertIdentical('4', $tree['49990 Node 4 4']['link']['nid']);
    $this->assertIdentical('5', $tree['49990 Node 4 4']['below']['50000 Node 5 5']['link']['nid']);
    $this->assertIdentical('6', $tree['49990 Node 4 4']['below']['50000 Node 5 5']['below']['50000 Node 6 6']['link']['nid']);
    $this->assertIdentical('7', $tree['49990 Node 4 4']['below']['50000 Node 5 5']['below']['50000 Node 7 7']['link']['nid']);
    $this->assertIdentical(array(), $tree['49990 Node 4 4']['below']['50000 Node 5 5']['below']['50000 Node 6 6']['below']);
    $this->assertIdentical(array(), $tree['49990 Node 4 4']['below']['50000 Node 5 5']['below']['50000 Node 7 7']['below']);
  }

}
