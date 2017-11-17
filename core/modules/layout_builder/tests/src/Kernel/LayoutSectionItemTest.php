<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\layout_builder\Field\LayoutSectionItemInterface;
use Drupal\layout_builder\Field\LayoutSectionItemListInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the field type for Layout Sections.
 *
 * @group layout_builder
 */
class LayoutSectionItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['layout_builder', 'layout_discovery'];

  /**
   * Tests using entity fields of the layout section field type.
   */
  public function testLayoutSectionItem() {
    layout_builder_add_layout_section_field('entity_test', 'entity_test');

    $entity = EntityTest::create();
    /** @var \Drupal\layout_builder\Field\LayoutSectionItemListInterface $field_list */
    $field_list = $entity->layout_builder__layout;

    // Test sample item generation.
    $field_list->generateSampleItems();
    $this->entityValidateAndSave($entity);

    $field = $field_list->get(0);
    $this->assertInstanceOf(LayoutSectionItemInterface::class, $field);
    $this->assertInstanceOf(FieldItemInterface::class, $field);
    $this->assertSame('section', $field->mainPropertyName());
    $this->assertSame('layout_onecol', $field->layout);
    $this->assertSame([], $field->layout_settings);
    $this->assertSame([], $field->section);
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutSectionItemList() {
    layout_builder_add_layout_section_field('entity_test', 'entity_test');

    $entity = EntityTest::create();
    /** @var \Drupal\layout_builder\Field\LayoutSectionItemListInterface $field_list */
    $field_list = $entity->layout_builder__layout;
    $this->assertInstanceOf(LayoutSectionItemListInterface::class, $field_list);
    $this->assertInstanceOf(FieldItemListInterface::class, $field_list);
    $entity->save();

    $field_list->appendItem(['layout' => 'layout_twocol']);
    $field_list->appendItem(['layout' => 'layout_onecol']);
    $field_list->appendItem(['layout' => 'layout_threecol_25_50_25']);
    $this->assertSame([
      ['layout' => 'layout_twocol'],
      ['layout' => 'layout_onecol'],
      ['layout' => 'layout_threecol_25_50_25'],
    ], $field_list->getValue());

    $field_list->addItem(1, ['layout' => 'layout_threecol_33_34_33']);
    $this->assertSame([
      ['layout' => 'layout_twocol'],
      ['layout' => 'layout_threecol_33_34_33'],
      ['layout' => 'layout_onecol'],
      ['layout' => 'layout_threecol_25_50_25'],
    ], $field_list->getValue());

    $field_list->addItem($field_list->count(), ['layout' => 'layout_twocol_bricks']);
    $this->assertSame([
      ['layout' => 'layout_twocol'],
      ['layout' => 'layout_threecol_33_34_33'],
      ['layout' => 'layout_onecol'],
      ['layout' => 'layout_threecol_25_50_25'],
      ['layout' => 'layout_twocol_bricks'],
    ], $field_list->getValue());
  }

}
