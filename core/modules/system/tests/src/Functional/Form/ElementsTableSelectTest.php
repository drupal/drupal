<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the tableselect form element for expected behavior.
 *
 * @group Form
 */
class ElementsTableSelectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the display of checkboxes when #multiple is TRUE.
   */
  public function testMultipleTrue() {

    $this->drupalGet('form_test/tableselect/multiple-true');

    $this->assertSession()->responseNotContains('Empty text.');

    // Test for the presence of the Select all rows tableheader.
    $this->assertNotEmpty($this->xpath('//th[@class="select-all"]'), 'Presence of the "Select all" checkbox.');

    $rows = ['row1', 'row2', 'row3'];
    foreach ($rows as $row) {
      $this->assertNotEmpty($this->xpath('//input[@type="checkbox"]', [$row]), "Checkbox for the value $row.");
    }
  }

  /**
   * Tests the display of radios when #multiple is FALSE.
   */
  public function testMultipleFalse() {
    $this->drupalGet('form_test/tableselect/multiple-false');

    $this->assertSession()->pageTextNotContains('Empty text.');

    // Test for the absence of the Select all rows tableheader.
    $this->assertSession()->elementNotExists('xpath', '//th[@class="select-all"]');

    $rows = ['row1', 'row2', 'row3'];
    foreach ($rows as $row) {
      $this->assertNotEmpty($this->xpath('//input[@type="radio"]', [$row], "Radio button value: $row"));
    }
  }

  /**
   * Tests the display when #colspan is set.
   */
  public function testTableSelectColSpan() {
    $this->drupalGet('form_test/tableselect/colspan');

    // Verify presence of column three and absence of column four.
    $this->assertSession()->pageTextContains('Three');
    $this->assertSession()->pageTextNotContains('Four');

    // There should be three labeled column headers and 1 for the input.
    $table_head = $this->xpath('//thead/tr/th');
    $this->assertCount(4, $table_head, 'There are four column headers');

    // The first two body rows should each have 5 table cells: One for the
    // radio, one cell in the first column, one cell in the second column,
    // and two cells in the third column which has colspan 2.
    for ($i = 0; $i <= 1; $i++) {
      $this->assertCount(5, $this->xpath('//tbody/tr[' . ($i + 1) . ']/td'), 'There are five cells in row ' . $i);
    }
    // The third row should have 3 cells, one for the radio, one spanning the
    // first and second column, and a third in column 3 (which has colspan 3).
    $this->assertCount(3, $this->xpath('//tbody/tr[3]/td'), 'There are three cells in row 3.');
  }

  /**
   * Tests the display of the #empty text when #options is an empty array.
   */
  public function testEmptyText() {
    $this->drupalGet('form_test/tableselect/empty-text');
    $this->assertSession()->pageTextContains('Empty text.');
  }

  /**
   * Tests the submission of single and multiple values when #multiple is TRUE.
   */
  public function testMultipleTrueSubmit() {

    // Test a submission with one checkbox checked.
    $edit = [];
    $edit['tableselect[row1]'] = TRUE;
    $this->drupalGet('form_test/tableselect/multiple-true');
    $this->submitForm($edit, 'Submit');

    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Submitted: row1 = row1');
    $assert_session->pageTextContains('Submitted: row2 = 0');
    $assert_session->pageTextContains('Submitted: row3 = 0');

    // Test a submission with multiple checkboxes checked.
    $edit['tableselect[row1]'] = TRUE;
    $edit['tableselect[row3]'] = TRUE;
    $this->drupalGet('form_test/tableselect/multiple-true');
    $this->submitForm($edit, 'Submit');

    $assert_session->pageTextContains('Submitted: row1 = row1');
    $assert_session->pageTextContains('Submitted: row2 = 0');
    $assert_session->pageTextContains('Submitted: row3 = row3');

  }

  /**
   * Tests submission of values when #multiple is FALSE.
   */
  public function testMultipleFalseSubmit() {
    $edit['tableselect'] = 'row1';
    $this->drupalGet('form_test/tableselect/multiple-false');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Submitted: row1');
  }

  /**
   * Tests the #js_select property.
   */
  public function testAdvancedSelect() {
    // When #multiple = TRUE a Select all checkbox should be displayed by default.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-true-default');
    $this->assertSession()->elementExists('xpath', '//th[@class="select-all"]');

    // When #js_select is set to FALSE, a "Select all" checkbox should not be displayed.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-true-no-advanced-select');
    $this->assertSession()->elementNotExists('xpath', '//th[@class="select-all"]');

    // A "Select all" checkbox never makes sense when #multiple = FALSE, regardless of the value of #js_select.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-false-default');
    $this->assertSession()->elementNotExists('xpath', '//th[@class="select-all"]');

    $this->drupalGet('form_test/tableselect/advanced-select/multiple-false-advanced-select');
    $this->assertSession()->elementNotExists('xpath', '//th[@class="select-all"]');
  }

  /**
   * Tests the whether the option checker gives an error on invalid
   * tableselect values for checkboxes.
   */
  public function testMultipleTrueOptionchecker() {

    [$header, $options] = _form_test_tableselect_get_data();

    $form['tableselect'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
    ];

    // Test with a valid value.
    [, , $errors] = $this->formSubmitHelper($form, ['tableselect' => ['row1' => 'row1']]);
    $this->assertFalse(isset($errors['tableselect']), 'Option checker allows valid values for checkboxes.');

    // Test with an invalid value.
    [, , $errors] = $this->formSubmitHelper($form, ['tableselect' => ['non_existing_value' => 'non_existing_value']]);
    $this->assertTrue(isset($errors['tableselect']), 'Option checker disallows invalid values for checkboxes.');

  }

  /**
   * Tests the whether the option checker gives an error on invalid
   * tableselect values for radios.
   */
  public function testMultipleFalseOptionchecker() {

    [$header, $options] = _form_test_tableselect_get_data();

    $form['tableselect'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => FALSE,
    ];

    // Test with a valid value.
    [, , $errors] = $this->formSubmitHelper($form, ['tableselect' => 'row1']);
    $this->assertFalse(isset($errors['tableselect']), 'Option checker allows valid values for radio buttons.');

    // Test with an invalid value.
    [, , $errors] = $this->formSubmitHelper($form, ['tableselect' => 'non_existing_value']);
    $this->assertTrue(isset($errors['tableselect']), 'Option checker disallows invalid values for radio buttons.');
  }

  /**
   * Helper function for the option check test to submit a form while collecting errors.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param array $edit
   *   An array containing post data.
   *
   * @return array
   *   An array containing the processed form, the form_state and any errors.
   *
   * @internal param $form_element A form element to test.
   *   A form element to test.
   */
  private function formSubmitHelper($form, $edit) {
    $form_id = $this->randomMachineName();
    $form_state = new FormState();

    $form['op'] = ['#type' => 'submit', '#value' => 'Submit'];
    // The form token CSRF protection should not interfere with this test, so we
    // bypass it by setting the token to FALSE.
    $form['#token'] = FALSE;

    $edit['form_id'] = $form_id;

    // Disable page redirect for forms submitted programmatically. This is a
    // solution to skip the redirect step (there are no pages, then the redirect
    // isn't possible).
    $form_state->disableRedirect();
    $form_state->setUserInput($edit);
    $form_state->setFormObject(new StubForm($form_id, $form));

    \Drupal::formBuilder()->prepareForm($form_id, $form, $form_state);

    \Drupal::formBuilder()->processForm($form_id, $form, $form_state);

    $errors = $form_state->getErrors();

    // Clear errors and messages.
    \Drupal::messenger()->deleteAll();
    $form_state->clearErrors();

    // Return the processed form together with form_state and errors
    // to allow the caller lowlevel access to the form.
    return [$form, $form_state, $errors];
  }

}
