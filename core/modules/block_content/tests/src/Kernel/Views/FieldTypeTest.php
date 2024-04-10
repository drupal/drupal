<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Views;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the Drupal\block_content\Plugin\views\field\Type handler.
 *
 * @group block_content
 */
class FieldTypeTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'block_content_test_views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_type'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['block_content_test_views']);
    }
  }

  /**
   * Tests the field type.
   */
  public function testFieldType(): void {
    $this->installEntitySchema('block_content');
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => FALSE,
    ]);
    $block_content = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $block_content->save();

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
