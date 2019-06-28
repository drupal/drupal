<?php

namespace Drupal\Tests\book\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\book\Plugin\migrate\source\d6\Book as D6Book;

/**
 * @covers \Drupal\book\Plugin\migrate\source\Book
 * @group book
 * @group legacy
 */
class BookTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['book', 'migrate_drupal', 'node'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['book'] = [
      [
        'mlid' => '1',
        'nid' => '4',
        'bid' => '4',
      ],
    ];
    $tests[0]['source_data']['menu_links'] = [
      [
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
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
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
      ],
    ];
    return $tests;
  }

  /**
   * @expectedDeprecation Book is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.x. Use \Drupal\book\Plugin\migrate\source\Book instead. See https://www.drupal.org/node/2947487 for more information.
   * @doesNotPerformAssertions
   */
  public function testDeprecatedPlugin() {
    new D6Book(
      [],
      'd6_book',
      [],
      $this->prophesize('Drupal\migrate\Plugin\MigrationInterface')->reveal(),
      $this->prophesize('Drupal\Core\State\StateInterface')->reveal(),
      $this->prophesize('Drupal\Core\Entity\EntityManagerInterface')->reveal()
    );
  }

}
