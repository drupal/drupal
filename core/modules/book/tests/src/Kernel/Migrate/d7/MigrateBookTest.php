<?php

namespace Drupal\Tests\book\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\Node;

/**
 * Tests migration of book structures from Drupal 7.
 *
 * @group migrate_drupal_7
 */
class MigrateBookTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'book',
    'menu_ui',
    'node',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node']);
    $this->installSchema('book', ['book']);
    $this->installSchema('node', ['node_access']);
    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_book',
    ]);
  }

  /**
   * Tests the Drupal 7 book structure to Drupal 8 migration.
   */
  public function testBook() {
    $nodes = Node::loadMultiple([1, 2, 4, 6]);
    $this->assertSame('8', $nodes[1]->book['bid']);
    $this->assertSame('6', $nodes[1]->book['pid']);

    $this->assertSame('4', $nodes[2]->book['bid']);
    $this->assertSame('6', $nodes[2]->book['pid']);

    $this->assertSame('4', $nodes[4]->book['bid']);
    $this->assertSame('0', $nodes[4]->book['pid']);

    $this->assertSame('4', $nodes[6]->book['bid']);
    $this->assertSame('4', $nodes[6]->book['pid']);

    $tree = \Drupal::service('book.manager')->bookTreeAllData(4);
    $this->assertSame('4', $tree['49990 is - The thing about Firefly 4']['link']['nid']);
    $this->assertSame('6', $tree['49990 is - The thing about Firefly 4']['below']['50000 Comments are closed :-( 6']['link']['nid']);
    $this->assertSame('2', $tree['49990 is - The thing about Firefly 4']['below']['50000 Comments are closed :-( 6']['below']['50000 The thing about Deep Space 9 2']['link']['nid']);
    $this->assertSame([], $tree['49990 is - The thing about Firefly 4']['below']['50000 Comments are closed :-( 6']['below']['50000 The thing about Deep Space 9 2']['below']);
  }

}
