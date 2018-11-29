<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTestBaseFieldDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

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
      OverridesSectionStorage::FIELD_NAME => $section_data,
    ]);
    $entity->save();
    return $entity->get(OverridesSectionStorage::FIELD_NAME);
  }

  /**
   * @covers ::equals
   */
  public function testEquals() {
    $this->sectionStorage->getSection(0)->setLayoutSettings(['foo' => 1]);

    $second_section_storage = clone $this->sectionStorage;
    $this->assertTrue($this->sectionStorage->equals($second_section_storage));

    $second_section_storage->getSection(0)->setLayoutSettings(['foo' => '1']);
    $this->assertFalse($this->sectionStorage->equals($second_section_storage));
  }

  /**
   * @covers ::equals
   */
  public function testEqualsNonSection() {
    $list = $this->prophesize(FieldItemListInterface::class);
    $this->assertFalse($this->sectionStorage->equals($list->reveal()));
  }

}
