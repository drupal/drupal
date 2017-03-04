<?php

namespace Drupal\block_content\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the Drupal\block_content\Plugin\views\field\Type handler.
 *
 * @group block_content
 */
class FieldTypeTest extends BlockContentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_type'];

  public function testFieldType() {
    $block_content = $this->createBlockContent();
    $expected_result[] = [
      'id' => $block_content->id(),
      'type' => $block_content->bundle(),
    ];
    $column_map = [
      'id' => 'id',
      'type:target_id' => 'type',
    ];

    $view = Views::getView('test_field_type');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, $column_map, 'The correct block_content type was displayed.');
  }

}
