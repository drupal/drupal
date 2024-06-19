<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the form API checkbox element.
 *
 * Various combinations of #default_value and #return_value are used.
 *
 * @group Form
 */
class CheckboxTest extends BrowserTestBase {

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

  public function testFormCheckbox(): void {
    // Ensure that the checked state is determined and rendered correctly for
    // tricky combinations of default and return values.
    foreach ([FALSE, NULL, TRUE, 0, '0', '', 1, '1', 'foobar', '1foobar'] as $default_value) {
      // Only values that can be used for array indices are supported for
      // #return_value, with the exception of integer 0, which is not supported.
      // @see \Drupal\Core\Render\Element\Checkbox::processCheckbox().
      foreach (['0', '', 1, '1', 'foobar', '1foobar'] as $return_value) {
        $form_array = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestCheckboxTypeJugglingForm', $default_value, $return_value);
        $form = (string) \Drupal::service('renderer')->renderRoot($form_array);
        if ($default_value === TRUE) {
          $checked = TRUE;
        }
        elseif ($return_value === '0') {
          $checked = ($default_value === '0');
        }
        elseif ($return_value === '') {
          $checked = ($default_value === '');
        }
        elseif ($return_value === 1 || $return_value === '1') {
          $checked = ($default_value === 1 || $default_value === '1');
        }
        elseif ($return_value === 'foobar') {
          $checked = ($default_value === 'foobar');
        }
        elseif ($return_value === '1foobar') {
          $checked = ($default_value === '1foobar');
        }
        $checked_in_html = str_contains($form, 'checked');
        $message = '#default_value is ' . var_export($default_value, TRUE) . ' #return_value is ' . var_export($return_value, TRUE) . '.';
        $this->assertSame($checked, $checked_in_html, $message);
      }
    }

    // Ensure that $form_state->getValues() is populated correctly for a
    // checkboxes group that includes a 0-indexed array of options.
    $this->drupalGet('form-test/checkboxes-zero/1');
    $this->submitForm([], 'Save');
    $results = json_decode($this->getSession()->getPage()->getContent());
    $this->assertSame([0, 0, 0], $results->checkbox_off, 'All three in checkbox_off are zeroes: off.');
    $this->assertSame(['0', 0, 0], $results->checkbox_zero_default, 'The first choice is on in checkbox_zero_default');
    $this->assertSame(['0', 0, 0], $results->checkbox_string_zero_default, 'The first choice is on in checkbox_string_zero_default');
    // Due to Mink driver differences, we cannot submit an empty checkbox value
    // to submitForm(), even if that empty value is the 'true' value for
    // the checkbox.
    $this->drupalGet('form-test/checkboxes-zero/1');
    $this->assertSession()->fieldExists('checkbox_off[0]')->check();
    $this->submitForm([], 'Save');
    $results = json_decode($this->getSession()->getPage()->getContent());
    $this->assertSame(['0', 0, 0], $results->checkbox_off, 'The first choice is on in checkbox_off but the rest is not');

    // Ensure that each checkbox is rendered correctly for a checkboxes group
    // that includes a 0-indexed array of options.
    $this->drupalGet('form-test/checkboxes-zero/0');
    $this->submitForm([], 'Save');
    $checkboxes = $this->xpath('//input[@type="checkbox"]');

    $this->assertCount(9, $checkboxes, 'Correct number of checkboxes found.');
    foreach ($checkboxes as $checkbox) {
      $checked = $checkbox->isChecked();
      $name = $checkbox->getAttribute('name');
      $this->assertSame($checked, $name == 'checkbox_zero_default[0]' || $name == 'checkbox_string_zero_default[0]', "Checkbox $name correctly checked");
    }
    // Due to Mink driver differences, we cannot submit an empty checkbox value
    // to submitForm(), even if that empty value is the 'true' value for
    // the checkbox.
    $this->drupalGet('form-test/checkboxes-zero/0');
    $this->assertSession()->fieldExists('checkbox_off[0]')->check();
    $this->submitForm([], 'Save');
    $checkboxes = $this->xpath('//input[@type="checkbox"]');

    $this->assertCount(9, $checkboxes, 'Correct number of checkboxes found.');
    foreach ($checkboxes as $checkbox) {
      $checked = $checkbox->isChecked();
      $name = (string) $checkbox->getAttribute('name');
      $this->assertSame($checked, $name == 'checkbox_off[0]' || $name == 'checkbox_zero_default[0]' || $name == 'checkbox_string_zero_default[0]', "Checkbox $name correctly checked");
    }
  }

}
