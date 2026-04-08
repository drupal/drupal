<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Unit\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\text\Plugin\Field\FieldType\TextFieldItemList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests TextFieldItemList.
 */
#[CoversClass(TextFieldItemList::class)]
#[Group('text')]
class TextFieldItemListTest extends UnitTestCase {

  /**
   * Tests ::defaultValuesFormValidate() if the field allows multiple values.
   *
   * Ensures that the add_more button is filtered out of the submitted values
   * before validation, preventing PHP warnings due to trying to access array
   * keys on a TranslatableMarkup object.
   */
  public function testDefaultValuesFormValidateWithMultipleCardinality(): void {
    $field_name = 'field_test_text';
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->once())
      ->method('getName')
      ->willReturn($field_name);
    $definition->expects($this->once())
      ->method('getSetting')
      ->with('allowed_formats')
      ->willReturn(['restricted_html']);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->once())
      ->method('getValue')
      ->with(['default_value_input', $field_name])
      ->willReturn([
        'add_more' => new TranslatableMarkup('Add another item'),
      ]);
    $form_state->expects($this->once())
      ->method('has')
      ->with('default_value_widget')
      ->willReturn(TRUE);
    $form_state->expects($this->once())
      ->method('get')
      ->with('default_value_widget')
      ->willReturn([]);

    $item_list = new TextFieldItemList($definition);
    $form = [];
    $item_list->defaultValuesFormValidate([], $form, $form_state);
  }

}
