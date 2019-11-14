<?php

namespace Drupal\Tests\book\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

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

}
