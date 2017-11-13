<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormState;
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

    // Set up three properties, one of them being computed.
    $property_definitions['0'] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $property_definitions['0']->expects($this->any())
      ->method('isComputed')
      ->willReturn(FALSE);
    $property_definitions['1'] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $property_definitions['1']->expects($this->any())
      ->method('isComputed')
      ->willReturn(FALSE);
    $property_definitions['2'] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $property_definitions['2']->expects($this->any())
      ->method('isComputed')
      ->willReturn(TRUE);

    $field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getPropertyDefinitions')
      ->will($this->returnValue($property_definitions));
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

    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_e */
    $field_item_e = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_e->setValue(['2']);

    // Tests field item lists where both have same values but different data
    // types.
    $datasets[] = [TRUE, $field_item_b, $field_item_e];

    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_f */
    $field_item_f = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_f->setValue(['0' => 1, '1' => 2, '2' => 3]);
    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_g */
    $field_item_g = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_g->setValue(['0' => 1, '1' => 2, '2' => 4]);

    // Tests field item lists where both have same values for the non-computed
    // properties ('0' and '1') and a different value for the computed one
    // ('2').
    $datasets[] = [TRUE, $field_item_f, $field_item_g];

    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_h */
    $field_item_h = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_h->setValue(['0' => 1, '1' => 2, '3' => 3]);
    /** @var \Drupal\Core\Field\FieldItemBase  $field_item_i */
    $field_item_i = $this->getMockForAbstractClass('Drupal\Core\Field\FieldItemBase', [], '', FALSE);
    $field_item_i->setValue(['0' => 1, '1' => 2, '3' => 4]);

    // Tests field item lists where both have same values for the non-computed
    // properties ('0' and '1') and a different value for a property that does
    // not exist ('3').
    $datasets[] = [TRUE, $field_item_h, $field_item_i];

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

    // Set up the properties of the field item.
    $property_definitions['0'] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $property_definitions['0']->expects($this->any())
      ->method('isComputed')
      ->willReturn(FALSE);
    $property_definitions['1'] = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $property_definitions['1']->expects($this->any())
      ->method('isComputed')
      ->willReturn(FALSE);

    $field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getPropertyDefinitions')
      ->will($this->returnValue($property_definitions));
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

  /**
   * @covers ::defaultValuesForm
   */
  public function testDefaultValuesForm() {
    $field_definition = $this->getMock(FieldDefinitionInterface::class);
    $field_definition->expects($this->any())
      ->method('getType')
      ->willReturn('field_type');
    /** @var \Drupal\Core\Field\FieldItemList|\PHPUnit_Framework_MockObject_MockObject $field_list */
    $field_list = $this->getMock(FieldItemList::class, ['defaultValueWidget'], [$field_definition]);
    $field_list->expects($this->any())
      ->method('defaultValueWidget')
      ->willReturn(NULL);
    $form = [];
    $form_state = new FormState();
    $string_translation = $this->getStringTranslationStub();
    $field_list->setStringTranslation($string_translation);

    $this->assertEquals('No widget available for: <em class="placeholder">field_type</em>.', $field_list->defaultValuesForm($form, $form_state)['#markup']);
  }

  /**
   * @covers ::defaultValuesFormValidate
   */
  public function testDefaultValuesFormValidate() {
    $field_definition = $this->getMock(FieldDefinitionInterface::class);
    /** @var \Drupal\Core\Field\FieldItemList|\PHPUnit_Framework_MockObject_MockObject $field_list */
    $field_list = $this->getMock(FieldItemList::class, ['defaultValueWidget', 'validate'], [$field_definition]);
    $field_list->expects($this->any())
      ->method('defaultValueWidget')
      ->willReturn(NULL);
    $field_list->expects($this->never())
      ->method('validate');
    $form = [];
    $form_state = new FormState();

    $field_list->defaultValuesFormValidate([], $form, $form_state);
  }

  /**
   * @covers ::defaultValuesFormSubmit
   */
  public function testDefaultValuesFormSubmit() {
    $field_definition = $this->getMock(FieldDefinitionInterface::class);
    /** @var \Drupal\Core\Field\FieldItemList|\PHPUnit_Framework_MockObject_MockObject $field_list */
    $field_list = $this->getMock(FieldItemList::class, ['defaultValueWidget', 'getValue'], [$field_definition]);
    $field_list->expects($this->any())
      ->method('defaultValueWidget')
      ->willReturn(NULL);
    $form = [];
    $form_state = new FormState();
    $field_list->expects($this->never())
      ->method('getValue');

    $this->assertNull($field_list->defaultValuesFormSubmit([], $form, $form_state));
  }

}
