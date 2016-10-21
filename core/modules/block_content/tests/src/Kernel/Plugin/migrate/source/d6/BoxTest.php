<?php

namespace Drupal\Tests\block_content\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 block boxes source plugin.
 *
 * @covers \Drupal\block_content\Plugin\migrate\source\d6\Box
 * @group block_content
 */
class BoxTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['source_data']['boxes'] = [
      [
        'bid' => 1,
        'body' => '<p>I made some custom content.</p>',
        'info' => 'Static Block',
        'format' => 1,
      ],
      [
        'bid' => 2,
        'body' => '<p>I made some more custom content.</p>',
        'info' => 'Test Content',
        'format' => 1,
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['boxes'];

    return $tests;
  }

}
