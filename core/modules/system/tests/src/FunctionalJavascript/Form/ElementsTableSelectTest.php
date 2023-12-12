<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the tableselect form element for expected behavior.
 *
 * @group Form
 */
class ElementsTableSelectTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the presence of ajax functionality for all options.
   */
  public function testAjax() {
    // Test checkboxes (#multiple == TRUE).
    $this->drupalGet('form_test/tableselect/multiple-true');
    $session = $this->getSession();
    $page = $session->getPage();
    for ($i = 1; $i <= 3; $i++) {
      $row = 'row' . $i;
      $page->hasUncheckedField($row);
      $page->checkField($row);
      $this->assertSession()->assertWaitOnAjaxRequest();
      // Check current row and previous rows are checked.
      for ($j = 1; $j <= $i; $j++) {
        $other_row = 'row' . $j;
        $page->hasCheckedField($other_row);
      }
    }

    // Test radios (#multiple == FALSE).
    $this->drupalGet('form_test/tableselect/multiple-false');
    for ($i = 1; $i <= 3; $i++) {
      $row = 'input[value="row' . $i . '"]';
      $page->hasUncheckedField($row);
      $this->click($row);
      $this->assertSession()->assertWaitOnAjaxRequest();
      $page->hasCheckedField($row);
      // Check other rows are not checked
      for ($j = 1; $j <= 3; $j++) {
        if ($j == $i) {
          continue;
        }
        $other_row = 'edit-tableselect-row' . $j;
        $page->hasUncheckedField($other_row);
      }
    }
  }

  /**
   * Tests table select with disabled rows.
   */
  public function testDisabledRows() {
    // Asserts that a row number (1 based) is enabled.
    $assert_row_enabled = function ($delta) {
      $row = $this->assertSession()->elementExists('xpath', "//table/tbody/tr[$delta]");
      $this->assertFalse($row->hasClass('disabled'));
      $input = $row->find('css', 'input[value="row' . $delta . '"]');
      $this->assertFalse($input->hasAttribute('disabled'));
    };
    // Asserts that a row number (1 based) is disabled.
    $assert_row_disabled = function ($delta) {
      $row = $this->assertSession()->elementExists('xpath', "//table/tbody/tr[$delta]");
      $this->assertTrue($row->hasClass('disabled'));
      $input = $row->find('css', 'input[value="row' . $delta . '"]');
      $this->assertTrue($input->hasAttribute('disabled'));
      $this->assertEquals('disabled', $input->getAttribute('disabled'));
    };

    // Test radios (#multiple == FALSE).
    $this->drupalGet('form_test/tableselect/disabled-rows/multiple-false');

    // Check that only 'row2' is disabled.
    $assert_row_enabled(1);
    $assert_row_disabled(2);
    $assert_row_enabled(3);

    // Test checkboxes (#multiple == TRUE).
    $this->drupalGet('form_test/tableselect/disabled-rows/multiple-true');

    // Check that only 'row2' is disabled.
    $assert_row_enabled(1);
    $assert_row_disabled(2);
    $assert_row_enabled(3);

    // Table select with checkboxes allow selection of all options.
    $select_all_checkbox = $this->assertSession()->elementExists('xpath', '//table/thead/tr/th/input');
    $select_all_checkbox->check();

    // Check that the disabled option was not enabled or selected.
    $page = $this->getSession()->getPage();
    $page->hasCheckedField('row1');
    $page->hasUncheckedField('row2');
    $assert_row_disabled(2);
    $page->hasCheckedField('row3');
  }

}
