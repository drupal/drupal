<?php

namespace Drupal\Tests\block_content\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests d7_block_custom source plugin.
 *
 * @covers \Drupal\block_content\Plugin\migrate\source\d7\BlockCustom
 * @group block_content
 */
class BlockCustomTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['source_data']['block_custom'] = [
      [
        'bid' => '1',
        'body' => "I don't feel creative enough to write anything clever here.",
        'info' => 'Meh',
        'format' => 'filtered_html',
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['block_custom'];

    return $tests;
  }

}
