<?php

/**
 * @file
 * Contains \Drupal\Tests\book\Unit\Plugin\migrate\source\d6\BookTest.
 */

namespace Drupal\Tests\book\Unit\Plugin\migrate\source\d6;

use Drupal\book\Plugin\migrate\source\d6\Book;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\book\Plugin\migrate\source\d6\Book
 * @group book
 */
class BookTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = Book::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_book',
    ),
  );

  protected $expectedResults = array(
    array(
      'nid' => '4',
      'bid' => '4',
      'mlid' => '1',
      'plid' => '0',
      'weight' => '-10',
      'p1' => '1',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['book'] = array(
      array(
        'mlid' => '1',
        'nid' => '4',
        'bid' => '4',
      ),
    );
    $this->databaseContents['menu_links'] = array(
      array(
        'menu_name' => 'book-toc-1',
        'mlid' => '1',
        'plid' => '0',
        'link_path' => 'node/4',
        'router_path' => 'node/%',
        'link_title' => 'Test top book title',
        'options' => 'a:0:{}',
        'module' => 'book',
        'hidden' => '0',
        'external' => '0',
        'has_children' => '1',
        'expanded' => '0',
        'weight' => '-10',
        'depth' => '1',
        'customized' => '0',
        'p1' => '1',
        'p2' => '0',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ),
    );
    parent::setUp();
  }

}
