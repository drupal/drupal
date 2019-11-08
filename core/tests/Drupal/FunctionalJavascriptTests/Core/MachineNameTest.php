<?php

namespace Drupal\FunctionalJavascriptTests\Core;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for the machine name field.
 *
 * @group field
 */
class MachineNameTest extends WebDriverTestBase {

  /**
   * Required modules.
   *
   * Node is required because the machine name callback checks for
   * access_content.
   *
   * @var array
   */
  public static $modules = ['node', 'form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests that machine name field functions.
   *
   * Makes sure that the machine name field automatically provides a valid
   * machine name and that the manual editing mode functions.
   */
  public function testMachineName() {
    // Visit the machine name test page which contains two machine name fields.
    $this->drupalGet('form-test/machine-name');

    // Test values for conversion.
    $test_values = [
      [
        'input' => 'Test value !0-9@',
        'message' => 'A title that should be transliterated must be equal to the php generated machine name',
        'expected' => 'test_value_0_9_',
      ],
      [
        'input' => 'Test value',
        'message' => 'A title that should not be transliterated must be equal to the php generated machine name',
        'expected' => 'test_value',
      ],
    ];

    // Get page and session.
    $page = $this->getSession()->getPage();

    // Get elements from the page.
    $title_1 = $page->findField('machine_name_1_label');
    $machine_name_1_field = $page->findField('machine_name_1');
    $machine_name_2_field = $page->findField('machine_name_2');
    $machine_name_1_wrapper = $machine_name_1_field->getParent();
    $machine_name_2_wrapper = $machine_name_2_field->getParent();
    $machine_name_1_value = $page->find('css', '#edit-machine-name-1-label-machine-name-suffix .machine-name-value');
    $machine_name_2_value = $page->find('css', '#edit-machine-name-2-label-machine-name-suffix .machine-name-value');
    $button_1 = $page->find('css', '#edit-machine-name-1-label-machine-name-suffix button.link');

    // Assert both fields are initialized correctly.
    $this->assertNotEmpty($machine_name_1_value, 'Machine name field 1 must be initialized');
    $this->assertNotEmpty($machine_name_2_value, 'Machine name field 2 must be initialized');

    // Field must be present for the rest of the test to work.
    if (empty($machine_name_1_value)) {
      $this->fail('Cannot finish test, missing machine name field');
    }

    // Test each value for conversion to a machine name.
    foreach ($test_values as $test_info) {
      // Set the value for the field, triggering the machine name update.
      $title_1->setValue($test_info['input']);

      // Wait the set timeout for fetching the machine name.
      $this->assertJsCondition('jQuery("#edit-machine-name-1-label-machine-name-suffix .machine-name-value").html() == "' . $test_info['expected'] . '"');

      // Validate the generated machine name.
      $this->assertEquals($test_info['expected'], $machine_name_1_value->getHtml(), $test_info['message']);

      // Validate the second machine name field is empty.
      $this->assertEmpty($machine_name_2_value->getHtml(), 'The second machine name field should still be empty');
    }

    // Validate the machine name field is hidden. Elements are visually hidden
    // using positioning, isVisible() will therefore not work.
    $this->assertEquals(TRUE, $machine_name_1_wrapper->hasClass('visually-hidden'), 'The ID field must not be visible');
    $this->assertEquals(TRUE, $machine_name_2_wrapper->hasClass('visually-hidden'), 'The ID field must not be visible');

    // Test switching back to the manual editing mode by clicking the edit link.
    $button_1->click();

    // Validate the visibility of the machine name field.
    $this->assertEquals(FALSE, $machine_name_1_wrapper->hasClass('visually-hidden'), 'The ID field must now be visible');

    // Validate the visibility of the second machine name field.
    $this->assertEquals(TRUE, $machine_name_2_wrapper->hasClass('visually-hidden'), 'The ID field must not be visible');

    // Validate if the element contains the correct value.
    $this->assertEquals($test_values[1]['expected'], $machine_name_1_field->getValue(), 'The ID field value must be equal to the php generated machine name');

    $assert = $this->assertSession();
    $this->drupalGet('/form-test/form-test-machine-name-validation');

    // Test errors after with no AJAX.
    $assert->buttonExists('Save')->press();
    $assert->pageTextContains('Machine-readable name field is required.');
    // Ensure only the first machine name field has an error.
    $this->assertTrue($assert->fieldExists('id')->hasClass('error'));
    $this->assertFalse($assert->fieldExists('id2')->hasClass('error'));

    // Test a successful submit after using AJAX.
    $assert->fieldExists('Name')->setValue('test 1');
    $assert->fieldExists('id')->setValue('test_1');
    $assert->selectExists('snack')->selectOption('apple');
    $assert->assertWaitOnAjaxRequest();
    $assert->buttonExists('Save')->press();
    $assert->pageTextContains('The form_test_machine_name_validation_form form has been submitted successfully.');

    // Test errors after using AJAX.
    $assert->fieldExists('Name')->setValue('duplicate');
    $this->assertJsCondition('document.forms[0].id.value === "duplicate"');
    $assert->fieldExists('id2')->setValue('duplicate2');
    $assert->selectExists('snack')->selectOption('potato');
    $assert->assertWaitOnAjaxRequest();
    $assert->buttonExists('Save')->press();
    $assert->pageTextContains('The machine-readable name is already in use. It must be unique.');
    // Ensure both machine name fields both have errors.
    $this->assertTrue($assert->fieldExists('id')->hasClass('error'));
    $this->assertTrue($assert->fieldExists('id2')->hasClass('error'));
  }

}
