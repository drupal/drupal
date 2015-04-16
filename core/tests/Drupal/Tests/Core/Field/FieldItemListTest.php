<?php

/**
 * @file Contains \Drupal\Tests\Core\Field\FieldItemListTest.
 */

namespace Drupal\Tests\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldItemList
 * @group Field
 */
class FieldItemListTest extends UnitTestCase {

  /**
   * @covers ::equals
   *
   * @dataProvider providerTestEquals
   */
  public function testEquals($expected, FieldItemInterface $first_field_item = NULL, FieldItemInterface $second_field_item = NULL) {

    // Mock the field type manager and place it in the container.
    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    \Drupal::setContainer($container);

    $field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getColumns')
      ->willReturn([0 => '0', 1 => '1']);
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->willReturn($field_storage_definition);

    $field_list_a = new FieldItemList($field_definition);
    $field_list_b = new FieldItemList($field_definition);

    // Set up the mocking necessary for creating field items.
    $field_type_manager->expects($this->any())
      ->method('createFieldItem')
      ->willReturnOnConsecutiveCalls($first_field_item, $second_field_item);

    // Set the field item values.
    if ($first_field_item instanceof FieldItemInterface) {
      $field_list_a->setValue($first_field_item);
    }
    if ($second_field_item instanceof FieldItemInterface) {
      $field_list_b->setValue($second_field_item);
    }

    $this->assertEquals($expected, $field_list_a->equals($field_list_b));
  }

  /**
   * Data provider for testEquals.
   */
  public function providerTestEquals() {
    // Tests field item lists with no values.
    $datasets[] = [TRUE];

    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_a */
    $field_item_a = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_a->setValue([1]);
    // Tests field item lists where one has a value and one does not.
    $datasets[] = [FALSE, $field_item_a];

    // Tests field item lists where both have the same value.
    $datasets[] = [TRUE, $field_item_a, $field_item_a];

    /** @var \Drupal\Core\Field\FieldItemBase  $fv */
    $field_item_b = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_b->setValue([2]);
    // Tests field item lists where both have the different values.
    $datasets[] = [FALSE, $field_item_a, $field_item_b];

    /** @var \Drupal\Core\Field\FieldItemBase  $fv */
    $field_item_c = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_c->setValue(['0' => 1, '1' => 2]);
    $field_item_d = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_d->setValue(['1' => 2, '0' => 1]);

    // Tests field item lists where both have the differently ordered values.
    $datasets[] = [TRUE, $field_item_c, $field_item_d];

    return $datasets;
  }

  /**
   * @covers ::equals
   */
  public function testEqualsEmptyItems() {
    /** @var \Drupal\Core\Field\FieldItemBase  $fv */
    $first_field_item = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $first_field_item->setValue(['0' => 1, '1' => 2]);
    $second_field_item = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $second_field_item->setValue(['1' => 2, '0' => 1]);
    $empty_field_item = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    // Mock the field type manager and place it in the container.
    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    \Drupal::setContainer($container);

    $field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getColumns')
      ->willReturn([0 => '0', 1 => '1']);
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->willReturn($field_storage_definition);

    $field_list_a = new FieldItemList($field_definition);
    $field_list_b = new FieldItemList($field_definition);

    // Set up the mocking necessary for creating field items.
    $field_type_manager->expects($this->any())
      ->method('createFieldItem')
      ->willReturnOnConsecutiveCalls($first_field_item, $second_field_item, $empty_field_item, $empty_field_item);

    // Set the field item values.
    $field_list_a->setValue($first_field_item);
    $field_list_b->setValue($second_field_item);
    $field_list_a->appendItem($empty_field_item);

    // Field list A has an empty item.
    $this->assertEquals(FALSE, $field_list_a->equals($field_list_b));

    // Field lists A and B have empty items.
    $field_list_b->appendItem($empty_field_item);
    $this->assertEquals(TRUE, $field_list_a->equals($field_list_b));

    // Field list B has an empty item.
    $field_list_a->filterEmptyItems();
    $this->assertEquals(FALSE, $field_list_a->equals($field_list_b));

    // Neither field lists A and B have empty items.
    $field_list_b->filterEmptyItems();
    $this->assertEquals(TRUE, $field_list_a->equals($field_list_b));
  }

}
