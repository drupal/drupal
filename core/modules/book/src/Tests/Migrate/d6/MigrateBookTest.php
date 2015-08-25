<?php

/**
 * @file
 * Contains \Drupal\book\Tests\Migrate\d6\MigrateBookTest.
 */

namespace Drupal\book\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Upgrade book structure.
 *
 * @group migrate_drupal_6
 */
class MigrateBookTest extends MigrateDrupal6TestBase {

  public static $modules = array('book', 'system', 'node', 'field', 'text', 'entity_reference', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('book', array('book'));
    $this->installSchema('node', array('node_access'));

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
    $this->executeMigration('d6_book');
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
