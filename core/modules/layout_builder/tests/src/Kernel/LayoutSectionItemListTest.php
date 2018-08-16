<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\entity_test\Entity\EntityTestBaseFieldDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Tests the field type for Layout Sections.
 *
 * @coversDefaultClass \Drupal\layout_builder\Field\LayoutSectionItemList
 *
 * @group layout_builder
 */
class LayoutSectionItemListTest extends SectionStorageTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSectionStorage(array $section_data) {
    $this->installEntitySchema('entity_test_base_field_display');
    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'entity_test_base_field_display',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    array_map(function ($row) {
      return ['section' => $row];
    }, $section_data);
    $entity = EntityTestBaseFieldDisplay::create([
      'name' => 'The test entity',
      'layout_builder__layout' => $section_data,
    ]);
    $entity->save();
    return $entity->get('layout_builder__layout');
  }

}
