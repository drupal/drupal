<?php

namespace Drupal\FunctionalJavascriptTests\Core\Form;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the state of elements based on another elements.
 *
 * @group javascript
 */
class JavascriptStatesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    // Add text formats.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();
    $normal_user = $this->drupalCreateUser([
      'use text format filtered_html',
      'use text format full_html',
    ]);
    $this->drupalLogin($normal_user);
  }

  /**
   * Tests the JavaScript #states functionality of form elements.
   *
   * To avoid the large cost of a dataProvider in FunctionalJavascript tests,
   * this is a single public test method that invokes a series of protected
   * methods to do assertions on specific kinds of triggering elements.
   */
  public function testJavascriptStates() {
    $this->doCheckboxTriggerTests();
    $this->doCheckboxesTriggerTests();
    $this->doTextfieldTriggerTests();
    $this->doRadiosTriggerTests();
    $this->doSelectTriggerTests();
    $this->doMultipleTriggerTests();
  }

  /**
   * Tests states of elements triggered by a checkbox element.
   */
  protected function doCheckboxTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $trigger = $page->findField('checkbox_trigger');
    $this->assertNotEmpty($trigger);
    $textfield_invisible_element = $page->findField('textfield_invisible_when_checkbox_trigger_checked');
    $this->assertNotEmpty($textfield_invisible_element);
    $textfield_required_element = $page->findField('textfield_required_when_checkbox_trigger_checked');
    $this->assertNotEmpty($textfield_required_element);
    $details = $this->assertSession()->elementExists('css', '#edit-details-expanded-when-checkbox-trigger-checked');
    $textfield_in_details = $details->findField('textfield_in_details');
    $this->assertNotEmpty($textfield_in_details);
    $checkbox_checked_element = $page->findField('checkbox_checked_when_checkbox_trigger_checked');
    $this->assertNotEmpty($checkbox_checked_element);
    $checkbox_unchecked_element = $page->findField('checkbox_unchecked_when_checkbox_trigger_checked');
    $this->assertNotEmpty($checkbox_unchecked_element);
    $checkbox_visible_element = $page->findField('checkbox_visible_when_checkbox_trigger_checked');
    $this->assertNotEmpty($checkbox_visible_element);
    $text_format_invisible_value = $page->findField('text_format_invisible_when_checkbox_trigger_checked[value]');
    $this->assertNotEmpty($text_format_invisible_value);
    $text_format_invisible_format = $page->findField('text_format_invisible_when_checkbox_trigger_checked[format]');
    $this->assertNotEmpty($text_format_invisible_format);

    // Verify initial state.
    $this->assertTrue($textfield_invisible_element->isVisible());
    $this->assertFalse($details->hasAttribute('open'));
    $this->assertFalse($textfield_in_details->isVisible());
    $this->assertFalse($textfield_required_element->hasAttribute('required'));
    $this->assertFalse($checkbox_checked_element->isChecked());
    $this->assertTrue($checkbox_unchecked_element->isChecked());
    $this->assertFalse($checkbox_visible_element->isVisible());
    $this->assertTrue($text_format_invisible_value->isVisible());
    $this->assertTrue($text_format_invisible_format->isVisible());

    // Change state: check the checkbox.
    $trigger->check();
    // Verify triggered state.
    $this->assertFalse($textfield_invisible_element->isVisible());
    $this->assertEquals('required', $textfield_required_element->getAttribute('required'));
    $this->assertTrue($details->hasAttribute('open'));
    $this->assertTrue($textfield_in_details->isVisible());
    $this->assertTrue($checkbox_checked_element->isChecked());
    $this->assertFalse($checkbox_unchecked_element->isChecked());
    $this->assertTrue($checkbox_visible_element->isVisible());
    $this->assertFalse($text_format_invisible_value->isVisible());
    $this->assertFalse($text_format_invisible_format->isVisible());
  }

  /**
   * Tests states of elements triggered by a checkboxes element.
   */
  protected function doCheckboxesTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $trigger_value1 = $page->findField('checkboxes_trigger[value1]');
    $this->assertNotEmpty($trigger_value1);
    $trigger_value2 = $page->findField('checkboxes_trigger[value2]');
    $this->assertNotEmpty($trigger_value2);
    $trigger_value3 = $page->findField('checkboxes_trigger[value3]');
    $this->assertNotEmpty($trigger_value3);
    $textfield_visible_value2 = $page->findField('textfield_visible_when_checkboxes_trigger_value2_checked');
    $this->assertNotEmpty($textfield_visible_value2);
    $textfield_visible_value3 = $page->findField('textfield_visible_when_checkboxes_trigger_value3_checked');
    $this->assertNotEmpty($textfield_visible_value3);

    // Verify initial state.
    $this->assertFalse($textfield_visible_value2->isVisible());
    $this->assertFalse($textfield_visible_value3->isVisible());
    // Change state: check the 'Value 1' checkbox.
    $trigger_value1->check();
    $this->assertFalse($textfield_visible_value2->isVisible());
    $this->assertFalse($textfield_visible_value3->isVisible());
    // Change state: check the 'Value 2' checkbox.
    $trigger_value2->check();
    $this->assertTrue($textfield_visible_value2->isVisible());
    $this->assertFalse($textfield_visible_value3->isVisible());
    // Change state: check the 'Value 3' checkbox.
    $trigger_value3->check();
    $this->assertTrue($textfield_visible_value2->isVisible());
    $this->assertTrue($textfield_visible_value3->isVisible());
    // Change state: uncheck the 'Value 2' checkbox.
    $trigger_value2->uncheck();
    $this->assertFalse($textfield_visible_value2->isVisible());
    $this->assertTrue($textfield_visible_value3->isVisible());
  }

  /**
   * Tests states of elements triggered by a textfield element.
   */
  protected function doTextfieldTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $trigger = $page->findField('textfield_trigger');
    $this->assertNotEmpty($trigger);
    $checkbox_checked_target = $page->findField('checkbox_checked_when_textfield_trigger_filled');
    $this->assertNotEmpty($checkbox_checked_target);
    $checkbox_unchecked_target = $page->findField('checkbox_unchecked_when_textfield_trigger_filled');
    $this->assertNotEmpty($checkbox_unchecked_target);
    $select_invisible_target = $page->findField('select_invisible_when_textfield_trigger_filled');
    $this->assertNotEmpty($select_invisible_target);
    $select_visible_target = $page->findField('select_visible_when_textfield_trigger_filled');
    $this->assertNotEmpty($select_visible_target);
    $textfield_required_target = $page->findField('textfield_required_when_textfield_trigger_filled');
    $this->assertNotEmpty($textfield_required_target);
    $details = $this->assertSession()->elementExists('css', '#edit-details-expanded-when-textfield-trigger-filled');
    $textfield_in_details = $details->findField('textfield_in_details');
    $this->assertNotEmpty($textfield_in_details);

    // Verify initial state.
    $this->assertFalse($checkbox_checked_target->isChecked());
    $this->assertTrue($checkbox_unchecked_target->isChecked());
    $this->assertTrue($select_invisible_target->isVisible());
    $this->assertFalse($select_visible_target->isVisible());
    $this->assertFalse($textfield_required_target->hasAttribute('required'));
    $this->assertFalse($details->hasAttribute('open'));
    $this->assertFalse($textfield_in_details->isVisible());

    // Change state: fill the textfield.
    $trigger->setValue('filled');
    // Verify triggered state.
    $this->assertTrue($checkbox_checked_target->isChecked());
    $this->assertFalse($checkbox_unchecked_target->isChecked());
    $this->assertFalse($select_invisible_target->isVisible());
    $this->assertTrue($select_visible_target->isVisible());
    $this->assertEquals('required', $textfield_required_target->getAttribute('required'));
    $this->assertTrue($details->hasAttribute('open'));
    $this->assertTrue($textfield_in_details->isVisible());
  }

  /**
   * Tests states of elements triggered by a radios element.
   */
  protected function doRadiosTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $trigger = $page->findField('radios_trigger');
    $this->assertNotEmpty($trigger);
    $fieldset_visible_when_value2 = $this->assertSession()->elementExists('css', '#edit-fieldset-visible-when-radios-trigger-has-value2');
    $textfield_in_fieldset = $fieldset_visible_when_value2->findField('textfield_in_fieldset');
    $this->assertNotEmpty($textfield_in_fieldset);
    $checkbox_checked_target = $page->findField('checkbox_checked_when_radios_trigger_has_value3');
    $this->assertNotEmpty($checkbox_checked_target);
    $checkbox_unchecked_target = $page->findField('checkbox_unchecked_when_radios_trigger_has_value3');
    $this->assertNotEmpty($checkbox_unchecked_target);
    $textfield_invisible_target = $page->findField('textfield_invisible_when_radios_trigger_has_value2');
    $this->assertNotEmpty($textfield_invisible_target);
    $select_required_target = $page->findField('select_required_when_radios_trigger_has_value2');
    $this->assertNotEmpty($select_required_target);
    $details = $this->assertSession()->elementExists('css', '#edit-details-expanded-when-radios-trigger-has-value3');
    $textfield_in_details = $details->findField('textfield_in_details');
    $this->assertNotEmpty($textfield_in_details);

    // Verify initial state, both the fieldset and something inside it.
    $this->assertFalse($fieldset_visible_when_value2->isVisible());
    $this->assertFalse($textfield_in_fieldset->isVisible());
    $this->assertFalse($checkbox_checked_target->isChecked());
    $this->assertTrue($checkbox_unchecked_target->isChecked());
    $this->assertTrue($textfield_invisible_target->isVisible());
    $this->assertFalse($select_required_target->hasAttribute('required'));
    $this->assertFalse($details->hasAttribute('open'));
    $this->assertFalse($textfield_in_details->isVisible());

    // Change state: select the value2 radios option.
    $trigger->selectOption('value2');
    // Verify triggered state.
    $this->assertTrue($fieldset_visible_when_value2->isVisible());
    $this->assertTrue($textfield_in_fieldset->isVisible());
    $this->assertFalse($textfield_invisible_target->isVisible());
    $this->assertTrue($select_required_target->hasAttribute('required'));
    // Checkboxes and details should not have changed state, yet.
    $this->assertFalse($checkbox_checked_target->isChecked());
    $this->assertTrue($checkbox_unchecked_target->isChecked());
    $this->assertFalse($details->hasAttribute('open'));
    $this->assertFalse($textfield_in_details->isVisible());
    // Change state: select the value3 radios option.
    $trigger->selectOption('value3');
    // Fieldset and contents should re-disappear.
    $this->assertFalse($fieldset_visible_when_value2->isVisible());
    $this->assertFalse($textfield_in_fieldset->isVisible());
    // Textfield and select should revert to initial state.
    $this->assertTrue($textfield_invisible_target->isVisible());
    $this->assertFalse($select_required_target->hasAttribute('required'));
    // Checkbox states should now change.
    $this->assertTrue($checkbox_checked_target->isChecked());
    $this->assertFalse($checkbox_unchecked_target->isChecked());
    // Details should now be expanded.
    $this->assertTrue($details->hasAttribute('open'));
    $this->assertTrue($textfield_in_details->isVisible());
  }

  /**
   * Tests states of elements triggered by a select element.
   */
  protected function doSelectTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $trigger = $page->findField('select_trigger');
    $this->assertNotEmpty($trigger);
    $item_visible_value2 = $this->assertSession()->elementExists('css', '#edit-item-visible-when-select-trigger-has-value2');
    $textfield_visible_value3 = $page->findField('textfield_visible_when_select_trigger_has_value3');
    $this->assertNotEmpty($textfield_visible_value3);
    $textfield_visible_value2_or_value3 = $page->findField('textfield_visible_when_select_trigger_has_value2_or_value3');
    $this->assertNotEmpty($textfield_visible_value2_or_value3);

    // Verify initial state.
    $this->assertFalse($item_visible_value2->isVisible());
    $this->assertFalse($textfield_visible_value3->isVisible());
    $this->assertFalse($textfield_visible_value2_or_value3->isVisible());
    // Change state: select the 'Value 2' option.
    $trigger->setValue('value2');
    $this->assertTrue($item_visible_value2->isVisible());
    $this->assertFalse($textfield_visible_value3->isVisible());
    $this->assertTrue($textfield_visible_value2_or_value3->isVisible());
    // Change state: select the 'Value 3' option.
    $trigger->setValue('value3');
    $this->assertFalse($item_visible_value2->isVisible());
    $this->assertTrue($textfield_visible_value3->isVisible());
    $this->assertTrue($textfield_visible_value2_or_value3->isVisible());
  }

  /**
   * Tests states of elements triggered by multiple elements.
   */
  protected function doMultipleTriggerTests() {
    $this->drupalGet('form-test/javascript-states-form');
    $page = $this->getSession()->getPage();

    // Find trigger and target elements.
    $select_trigger = $page->findField('select_trigger');
    $this->assertNotEmpty($select_trigger);
    $textfield_trigger = $page->findField('textfield_trigger');
    $this->assertNotEmpty($textfield_trigger);
    $item_visible_value2_and_textfield = $this->assertSession()->elementExists('css', '#edit-item-visible-when-select-trigger-has-value2-and-textfield-trigger-filled');

    // Verify initial state.
    $this->assertFalse($item_visible_value2_and_textfield->isVisible());
    // Change state: select the 'Value 2' option.
    $select_trigger->setValue('value2');
    $this->assertFalse($item_visible_value2_and_textfield->isVisible());
    // Change state: fill the textfield.
    $textfield_trigger->setValue('filled');
    $this->assertTrue($item_visible_value2_and_textfield->isVisible());
  }

}
