<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that FAPI correctly determines the triggering element.
 *
 * @group Form
 */
class TriggeringElementTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the triggering element when no button information is included.
   *
   * Test the determination of the triggering element when no button
   * information is included in the POST data, as is sometimes the case when
   * the ENTER key is pressed in a textfield in Internet Explorer.
   */
  public function testNoButtonInfoInPost() {
    $path = '/form-test/clicked-button';
    $form_html_id = 'form-test-clicked-button';

    // Ensure submitting a form with no buttons results in no triggering element
    // and the form submit handler not running.
    $this->drupalGet($path);

    $assert_session = $this->assertSession();
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('There is no clicked button.');
    $assert_session->pageTextNotContains('Submit handler for form_test_clicked_button executed.');

    // Ensure submitting a form with one or more submit buttons results in the
    // triggering element being set to the first one the user has access to. An
    // argument with 'r' in it indicates a restricted (#access=FALSE) button.
    $this->drupalGet($path . '/s');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button1.');
    $assert_session->pageTextContains('Submit handler for form_test_clicked_button executed.');

    $this->drupalGet($path . '/s/s');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button1.');
    $assert_session->pageTextContains('Submit handler for form_test_clicked_button executed.');

    $this->drupalGet($path . '/rs/s');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button2.');
    $assert_session->pageTextContains('Submit handler for form_test_clicked_button executed.');

    // Ensure submitting a form with buttons of different types results in the
    // triggering element being set to the first button, regardless of type. For
    // the FAPI 'button' type, this should result in the submit handler not
    // executing. The types are 's' (submit), 'b' (button), and 'i'
    // (image_button).
    $this->drupalGet($path . '/s/b/i');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button1.');
    $assert_session->pageTextContains('Submit handler for form_test_clicked_button executed.');

    $this->drupalGet($path . '/b/s/i');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button1.');
    $assert_session->pageTextNotContains('Submit handler for form_test_clicked_button executed.');

    $this->drupalGet($path . '/i/s/b');
    $this->getSession()->getDriver()->submitForm('//form[@id="' . $form_html_id . '"]');
    $assert_session->pageTextContains('The clicked button is button1.');
    $assert_session->pageTextContains('Submit handler for form_test_clicked_button executed.');
  }

  /**
   * Tests attempts to bypass access control.
   *
   * Test that the triggering element does not get set to a button with
   * #access=FALSE.
   */
  public function testAttemptAccessControlBypass() {
    $path = 'form-test/clicked-button';
    $form_html_id = 'form-test-clicked-button';

    // Retrieve a form where 'button1' has #access=FALSE and 'button2' doesn't.
    $this->drupalGet($path . '/rs/s');

    // Submit the form with 'button1=button1' in the POST data, which someone
    // trying to get around security safeguards could easily do. We have to do
    // a little trickery here, to work around the safeguards in submitForm()
    // by renaming the text field and value that is in the form to 'button1',
    // we can get the data we want into \Drupal::request()->request.
    $page = $this->getSession()->getPage();
    $input = $page->find('css', 'input[name="text"]');
    $this->assertNotNull($input, 'text input located.');

    $input->setValue('name', 'button1');
    $input->setValue('value', 'button1');
    $this->xpath('//form[@id="' . $form_html_id . '"]//input[@type="submit"]')[0]->click();

    // Ensure that the triggering element was not set to the restricted button.
    // Do this with both a negative and positive assertion, because negative
    // assertions alone can be brittle. See testNoButtonInfoInPost() for why the
    // triggering element gets set to 'button2'.
    $this->assertSession()->pageTextNotContains('The clicked button is button1.');
    $this->assertSession()->pageTextContains('The clicked button is button2.');
  }

}
