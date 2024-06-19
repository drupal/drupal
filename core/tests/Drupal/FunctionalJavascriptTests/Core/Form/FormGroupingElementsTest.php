<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Core\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for form grouping elements.
 *
 * @group form
 */
class FormGroupingElementsTest extends WebDriverTestBase {

  /**
   * Required modules.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
  }

  /**
   * Tests that vertical tab children become visible.
   *
   * Makes sure that a child element of a vertical tab that is not visible,
   * becomes visible when the tab is clicked, a fragment link to the child is
   * clicked or when the URI fragment pointing to that child changes.
   */
  public function testVerticalTabChildVisibility(): void {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Request the group vertical tabs testing page with a fragment identifier
    // to the second element.
    $this->drupalGet('form-test/group-vertical-tabs', ['fragment' => 'edit-element-2']);

    $page = $session->getPage();

    $tab_link_1 = $page->find('css', '.vertical-tabs__menu-item > a');

    $child_1_selector = '#edit-element';
    $child_1 = $page->find('css', $child_1_selector);

    $child_2_selector = '#edit-element-2';
    $child_2 = $page->find('css', $child_2_selector);

    // Assert that the child in the second vertical tab becomes visible.
    // It should be visible after initial load due to the fragment in the URI.
    $this->assertTrue($child_2->isVisible(), 'Child 2 is visible due to a URI fragment');

    // Click on a fragment link pointing to an invisible child inside an
    // inactive vertical tab.
    $session->executeScript("jQuery('<a href=\"$child_1_selector\"></a>').insertAfter('h1')[0].click()");

    // Assert that the child in the first vertical tab becomes visible.
    $web_assert->waitForElementVisible('css', $child_1_selector, 50);

    // Trigger a URI fragment change (hashchange) to show the second vertical
    // tab again.
    $session->executeScript("location.replace('$child_2_selector')");

    // Assert that the child in the second vertical tab becomes visible again.
    $web_assert->waitForElementVisible('css', $child_2_selector, 50);

    $tab_link_1->click();

    // Assert that the child in the first vertical tab is visible again after
    // a click on the first tab.
    $this->assertTrue($child_1->isVisible(), 'Child 1 is visible after clicking the parent tab');
  }

  /**
   * Tests that details element children become visible.
   *
   * Makes sure that a child element of a details element that is not visible,
   * becomes visible when a fragment link to the child is clicked or when the
   * URI fragment pointing to that child changes.
   */
  public function testDetailsChildVisibility(): void {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Store reusable JavaScript code to remove the current URI fragment and
    // close all details.
    $reset_js = "location.replace('#'); jQuery('details').removeAttr('open')";

    // Request the group details testing page.
    $this->drupalGet('form-test/group-details');

    $page = $session->getPage();

    $session->executeScript($reset_js);

    $child_selector = '#edit-element';
    $child = $page->find('css', $child_selector);

    // Assert that the child is not visible.
    $this->assertFalse($child->isVisible(), 'Child is not visible');

    // Trigger a URI fragment change (hashchange) to open all parent details
    // elements of the child.
    $session->executeScript("location.replace('$child_selector')");

    // Assert that the child becomes visible again after a hash change.
    $web_assert->waitForElementVisible('css', $child_selector, 50);

    $session->executeScript($reset_js);

    // Click on a fragment link pointing to an invisible child inside a closed
    // details element.
    $session->executeScript("jQuery('<a href=\"$child_selector\"></a>').insertAfter('h1')[0].click()");

    // Assert that the child is visible again after a fragment link click.
    $web_assert->waitForElementVisible('css', $child_selector, 50);

    // Find the summary belonging to the closest details element.
    $summary = $page->find('css', '#edit-meta > summary');

    // Assert that both aria-expanded and aria-pressed are true.
    $this->assertEquals('true', $summary->getAttribute('aria-expanded'));
  }

  /**
   * Confirms tabs containing a field with a validation error are open.
   */
  public function testVerticalTabValidationVisibility(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('form-test/group-vertical-tabs');
    $page->clickLink('Second group element');
    $input_field = $assert_session->waitForField('element_2');
    $this->assertNotNull($input_field);

    // Enter a value that will trigger a validation error.
    $input_field->setValue('bad');

    // Switch to a tab that does not have the error-causing field.
    $page->clickLink('First group element');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-meta'));

    // Submit the form.
    $page->pressButton('Save');

    // Confirm there is an error.
    $assert_session->waitForText('there was an error');

    // Confirm the tab containing the field with error is open.
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[name="element_2"].error'));
  }

  /**
   * Tests form submit with a required field in closed details element.
   */
  public function testDetailsContainsRequiredTextfield(): void {
    $this->drupalGet('form_test/details-contains-required-textfield');
    $details = $this->assertSession()->elementExists('css', 'details[data-drupal-selector="edit-meta"]');

    // Make sure details element is not open at the beginning.
    $this->assertFalse($details->hasAttribute('open'));

    $textfield = $this->assertSession()->elementExists('css', 'input[name="required_textfield_in_details"]');

    // The text field inside the details element is not visible too.
    $this->assertFalse($textfield->isVisible(), 'Text field is not visible');

    // Submit the form with invalid data in the required fields.
    $this->assertSession()
      ->elementExists('css', 'input[data-drupal-selector="edit-submit"]')
      ->click();
    // Confirm the required field is visible.
    $this->assertTrue($textfield->isVisible(), 'Text field is visible');
  }

  /**
   * Tests required field in closed details element with ajax form.
   */
  public function testDetailsContainsRequiredTextfieldAjaxForm(): void {
    $this->drupalGet('form_test/details-contains-required-textfield/true');
    $assert_session = $this->assertSession();
    $textfield = $assert_session->elementExists('css', 'input[name="required_textfield_in_details"]');

    // Submit the ajax form to open the details element at the first time.
    $assert_session->elementExists('css', 'input[value="Submit Ajax"]')
      ->click();

    $assert_session->waitForElementVisible('css', 'input[name="required_textfield_in_details"]');

    // Close the details element.
    $assert_session->elementExists('css', 'form summary')
      ->click();

    // Submit the form with invalid data in the required fields without ajax.
    $assert_session->elementExists('css', 'input[data-drupal-selector="edit-submit"]')
      ->click();

    // Confirm the required field is visible.
    $this->assertTrue($textfield->isVisible(), 'Text field is visible');
  }

}
