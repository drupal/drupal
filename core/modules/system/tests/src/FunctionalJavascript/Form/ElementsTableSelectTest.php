<?php

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

}
