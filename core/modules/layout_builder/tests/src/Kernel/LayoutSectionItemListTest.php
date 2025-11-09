<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTestBaseFieldDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Field\LayoutSectionItemList;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the field type for Layout Sections.
 */
#[CoversClass(LayoutSectionItemList::class)]
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutSectionItemListTest extends SectionListTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
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
   * Tests equals.
   *
   * @legacy-covers ::equals
   */
  public function testEquals(): void {
    $this->sectionList->getSection(0)->setLayoutSettings(['foo' => 1]);

    $second_section_storage = clone $this->sectionList;
    $this->assertTrue($this->sectionList->equals($second_section_storage));

    $second_section_storage->getSection(0)->setLayoutSettings(['foo' => '1']);
    $this->assertFalse($this->sectionList->equals($second_section_storage));
  }

  /**
   * Tests equals non section.
   *
   * @legacy-covers ::equals
   */
  public function testEqualsNonSection(): void {
    $list = $this->prophesize(FieldItemListInterface::class);
    $this->assertFalse($this->sectionList->equals($list->reveal()));
  }

}
