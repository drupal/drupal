<?php

/**
 * @file
 * Contains \Drupal\Tests\block_content\Unit\Plugin\migrate\source\d7\BlockCustomTest.
 */

namespace Drupal\Tests\block_content\Unit\Plugin\migrate\source\d7;

use Drupal\block_content\Plugin\migrate\source\d7\BlockCustom;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\block_content\Plugin\migrate\source\d7\BlockCustom
 * @group block_content
 */
class BlockCustomTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = BlockCustom::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_block_custom',
    ),
  );

  protected $expectedResults = array(
    array(
      'bid' => '1',
      'body' => "I don't feel creative enough to write anything clever here.",
      'info' => 'Meh',
      'format' => 'filtered_html',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['block_custom'] = $this->expectedResults;
    parent::setUp();
  }

}
