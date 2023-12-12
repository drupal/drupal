<?php

declare(strict_types=1);

namespace Drupal\Tests\field\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests widget form for a multiple value field.
 *
 * @group field
 */
class MultipleValueWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);

    $field = [
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'weight' => mt_rand(0, 127),
      'settings' => [
        'test_field_setting' => $this->randomMachineName(),
      ],
    ];

    FieldStorageConfig::create([
      'field_name' => 'field_unlimited',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create($field)->save();

    $entity_form_display = EntityFormDisplay::load($field['entity_type'] . '.' . $field['bundle'] . '.default');
    $entity_form_display->setComponent($field['field_name'])->save();
  }

  /**
   * Tests the 'Add more' functionality.
   */
  public function testFieldMultipleValueWidget() {
    $this->drupalGet('entity_test/add');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $add_more_button = $page->findButton('field_unlimited_add_more');

    // First set a value on the first input field.
    $field_0 = $page->findField('field_unlimited[0][value]');
    $field_0->setValue('1');

    $field_0_remove_button = $page->findButton('field_unlimited_0_remove_button');
    $this->assertNotEmpty($field_0_remove_button, 'First field has a remove button.');

    // Add another item.
    $add_more_button->click();
    $field_1 = $assert_session->waitForField('field_unlimited[1][value]');
    $this->assertNotEmpty($field_1, 'Successfully added another item.');

    $field_1_remove_button = $page->findButton('field_unlimited_1_remove_button');
    $this->assertNotEmpty($field_1_remove_button, 'Also second field has a remove button.');

    // Validate the value of the first field has not changed.
    $this->assertEquals('1', $field_0->getValue(), 'Value for the first item has not changed.');

    // Validate the value of the second item is empty.
    $this->assertEmpty($field_1->getValue(), 'Value for the second item is currently empty.');

    // Add another item.
    $add_more_button->click();
    $field_2 = $assert_session->waitForField('field_unlimited[2][value]');
    $this->assertNotEmpty($field_2, 'Successfully added another item.');

    // Set values for the 2nd and 3rd fields to validate dragging.
    $field_1->setValue('2');
    $field_2->setValue('3');

    $field_weight_0 = $page->findField('field_unlimited[0][_weight]');
    $field_weight_1 = $page->findField('field_unlimited[1][_weight]');
    $field_weight_2 = $page->findField('field_unlimited[2][_weight]');

    // Assert starting situation matches expectations.
    $this->assertGreaterThan($field_weight_0->getValue(), $field_weight_1->getValue());
    $this->assertGreaterThan($field_weight_1->getValue(), $field_weight_2->getValue());

    // Drag the first row after the third row.
    $dragged = $field_0->find('xpath', 'ancestor::tr[contains(@class, "draggable")]//a[@class="tabledrag-handle"]');
    $target = $field_2->find('xpath', 'ancestor::tr[contains(@class, "draggable")]');
    $dragged->dragTo($target);

    // Assert the order of items is updated correctly after dragging.
    $this->assertGreaterThan($field_weight_2->getValue(), $field_weight_0->getValue());
    $this->assertGreaterThan($field_weight_1->getValue(), $field_weight_2->getValue());

    // Validate the order of items conforms to the last drag action after a
    // updating the form via the server.
    $add_more_button->click();
    $field_3 = $assert_session->waitForField('field_unlimited[3][value]');
    $this->assertNotEmpty($field_3);
    $this->assertGreaterThan($field_weight_2->getValue(), $field_weight_0->getValue());
    $this->assertGreaterThan($field_weight_1->getValue(), $field_weight_2->getValue());

    // Validate no extraneous widget is displayed.
    $element = $page->findField('field_unlimited[4][value]');
    $this->assertEmpty($element);

    // Test removing items/values.
    $field_0_remove_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Test the updated widget.
    // First item is the initial second item.
    $this->assertEquals('2', $field_0->getValue(), 'Value for the first item has changed.');
    // We do not have the initial first item anymore.
    $this->assertEmpty($field_2->getValue(), 'Value for the third item is currently empty.');
    $element = $page->findField('field_unlimited[3][value]');
    $this->assertEmpty($element);

    // We can also remove empty items.
    $field_2_remove_button = $page->findButton('field_unlimited_2_remove_button');
    $field_2_remove_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $element = $page->findField('field_unlimited[2][value]');
    $this->assertEmpty($element, 'Empty field also removed.');

    // Assert that the wrapper exists and isn't nested.
    $this->assertSession()->elementsCount('css', '[data-drupal-selector="edit-field-unlimited-wrapper"]', 1);

    // Test removing items/values on saved entities resets to initial value.
    $this->submitForm([], 'Save');
    $field_2_remove_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $field_1_remove_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $field_0_remove_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $add_more_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSame('', $field_0->getValue());
    $add_more_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSame('', $field_1->getValue());
  }

}
