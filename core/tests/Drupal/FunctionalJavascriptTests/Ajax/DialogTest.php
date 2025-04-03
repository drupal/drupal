<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\ajax_test\Controller\AjaxTestController;
use Drupal\Core\Ajax\OpenModalDialogWithUrl;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

// cspell:ignore testdialog

/**
 * Performs tests on opening and manipulating dialogs via AJAX commands.
 *
 * @group Ajax
 */
class DialogTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test', 'ajax_forms_test', 'contact'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests sending non-JS and AJAX requests to open and manipulate modals.
   */
  public function testDialog(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer contact forms']));
    // Ensure the elements render without notices or exceptions.
    $this->drupalGet('ajax-test/dialog');

    // Set up variables for this test.
    $dialog_renderable = AjaxTestController::dialogContents();
    $dialog_contents = \Drupal::service('renderer')->renderRoot($dialog_renderable);

    // Check that requesting a modal dialog without JS goes to a page.
    $this->drupalGet('ajax-test/dialog-contents');
    $this->assertSession()->responseContains($dialog_contents);

    // Visit the page containing the many test dialog links.
    $this->drupalGet('ajax-test/dialog');

    // Tests a basic modal dialog by verifying the contents of the dialog are as
    // expected.
    $this->getSession()->getPage()->clickLink('Link 1 (modal)');

    // Clicking the link triggers an AJAX request/response.
    // Opens a Dialog panel.
    $link1_dialog_div = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertEquals('true', $link1_dialog_div->getAttribute('aria-modal'), 'Dialog modal has aria-modal attribute');
    $this->assertNotNull($link1_dialog_div, 'Link was used to open a dialog ( modal )');

    $link1_modal = $link1_dialog_div->find('css', '#drupal-modal');
    $this->assertNotNull($link1_modal, 'Link was used to open a dialog ( non-modal )');
    $this->assertSession()->responseContains($dialog_contents);

    $dialog_title = $link1_dialog_div->find('css', "h1.ui-dialog-title:contains('AJAX Dialog & contents')");
    $this->assertNotNull($dialog_title);
    $dialog_title_amp = $link1_dialog_div->find('css', "h1.ui-dialog-title:contains('AJAX Dialog &amp; contents')");
    $this->assertNull($dialog_title_amp);

    // Close open dialog, return to the dialog links page.
    $close_button = $link1_dialog_div->findButton('Close');
    $this->assertNotNull($close_button);
    $close_button->press();

    // Tests a modal with a dialog-option.
    // Link 2 is similar to Link 1, except it submits additional width
    // information which must be echoed in the resulting  DOM update.
    $this->getSession()->getPage()->clickLink('Link 2 (modal)');
    $dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertNotNull($dialog, 'Link was used to open a dialog ( non-modal, with options )');
    $style = $dialog->getAttribute('style');
    $this->assertStringContainsString('width: 400px;', $style, "Modal respected the dialog-options width parameter.  Style = $style");

    // Reset: Return to the dialog links page.
    $this->drupalGet('ajax-test/dialog');

    // Test a non-modal dialog ( with target ).
    $this->clickLink('Link 3 (non-modal)');
    $non_modal_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertNull($non_modal_dialog->getAttribute('aria-modal'), 'Dialog modal has no aria-modal attribute');
    $this->assertNotNull($non_modal_dialog, 'Link opens a non-modal dialog.');
    $non_modal_dialog_title = $non_modal_dialog->find('css', "h2.ui-dialog-title:contains('AJAX Dialog & contents')");
    $this->assertNotNull($non_modal_dialog_title);

    // Tests the dialog contains a target element specified in the AJAX request.
    $non_modal_dialog->find('css', 'div#ajax-test-dialog-wrapper-1');
    $this->assertSession()->responseContains($dialog_contents);

    // Reset: Return to the dialog links page.
    $this->drupalGet('ajax-test/dialog');

    // Tests a non-modal dialog ( without target ).
    $this->clickLink('Link 7 (non-modal, no target)');
    $no_target_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertNotNull($no_target_dialog, 'Link opens a non-modal dialog.');

    $contents_no_target = $no_target_dialog->find('css', 'div.ui-dialog-content');
    $this->assertNotNull($contents_no_target, 'non-modal dialog opens ( no target ). ');
    $id = $contents_no_target->getAttribute('id');
    $partial_match = str_starts_with($id, 'drupal-dialog-ajax-testdialog-contents');
    $this->assertTrue($partial_match, 'The non-modal ID has the expected prefix.');

    $no_target_button = $no_target_dialog->findButton('Close');
    $this->assertNotNull($no_target_button, 'Link dialog has a close button');
    $no_target_button->press();

    $this->getSession()->getPage()->findButton('Button 1 (modal)')->press();
    $button1_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertNotNull($button1_dialog, 'Button opens a modal dialog.');

    $button1_dialog_content = $button1_dialog->find('css', 'div.ui-dialog-content');
    $this->assertNotNull($button1_dialog_content, 'Button opens a modal dialog.');

    // Test the HTML escaping of & character.
    $button1_dialog_title = $button1_dialog->find('css', "h1.ui-dialog-title:contains('AJAX Dialog & contents')");
    $this->assertNotNull($button1_dialog_title);
    $button1_dialog_title_amp = $button1_dialog->find('css', "h1.ui-dialog-title:contains('AJAX Dialog &amp; contents')");
    $this->assertNull($button1_dialog_title_amp);

    // Reset: Close the dialog.
    $button1_dialog->findButton('Close')->press();

    // Abbreviated test for "normal" dialogs, testing only the difference.
    $this->getSession()->getPage()->findButton('Button 2 (non-modal)')->press();
    $button2_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog-content');
    $this->assertNotNull($button2_dialog, 'Non-modal content displays as expected.');

    // Use a link to close the panel opened by button 2.
    $this->getSession()->getPage()->clickLink('Link 4 (close non-modal if open)');

    // Test dialogs opened using OpenModalDialogWithUrl.
    $this->getSession()->getPage()->findButton('Button 3 (modal from url)')->press();
    // Check that title was fetched properly.
    // @see \Drupal\ajax_test\Form\AjaxTestDialogForm::dialog.
    $form_dialog_title = $this->assertSession()->waitForElement('css', "h1.ui-dialog-title:contains('Ajax Form contents')");
    $this->assertNotNull($form_dialog_title, 'Dialog form has the expected title.');
    $button1_dialog->findButton('Close')->press();
    // Test external URL.
    $dialog_obj = new OpenModalDialogWithUrl('http://example.com', []);
    try {
      $dialog_obj->render();
    }
    catch (\LogicException $e) {
      $this->assertEquals('External URLs are not allowed.', $e->getMessage());
    }

    // Form modal.
    $this->clickLink('Link 5 (form)');
    // Two links have been clicked in succession - This time wait for a change
    // in the title as the previous closing dialog may temporarily be open.
    $form_dialog_title = $this->assertSession()->waitForElementVisible('css', "h1.ui-dialog-title:contains('Ajax Form contents')");
    $this->assertNotNull($form_dialog_title, 'Dialog form has the expected title.');
    // Locate the newly opened dialog.
    $form_dialog = $this->getSession()->getPage()->find('css', 'div.ui-dialog');
    $this->assertNotNull($form_dialog, 'Form dialog is visible');

    $form_contents = $form_dialog->find('css', "p:contains('Ajax Form contents description.')");
    $this->assertNotNull($form_contents, 'For has the expected text.');
    $do_it = $form_dialog->findButton('Do it');
    $this->assertNotNull($do_it, 'The dialog has a "Do it" button.');
    $preview = $form_dialog->findButton('Preview');
    $this->assertNotNull($preview, 'The dialog contains a "Preview" button.');

    // Form submit inputs, link buttons, and buttons in dialog are copied to the
    // dialog buttonpane as buttons. The originals should have their styles set
    // to display: none.
    $hidden_buttons = $this->getSession()->getPage()->findAll('css', '.ajax-test-form .button');
    $this->assertCount(3, $hidden_buttons);
    $hidden_button_text = [];
    foreach ($hidden_buttons as $button) {
      $styles = $button->getAttribute('style');
      $this->assertStringContainsStringIgnoringCase('display: none;', $styles);
      $hidden_button_text[] = $button->hasAttribute('value') ? $button->getAttribute('value') : $button->getHtml();
    }

    // The copied buttons should have the same text as the submit inputs they
    // were copied from.
    $moved_to_buttonpane_buttons = $this->getSession()->getPage()->findAll('css', '.ui-dialog-buttonpane button');
    $this->assertCount(3, $moved_to_buttonpane_buttons);
    foreach ($moved_to_buttonpane_buttons as $key => $button) {
      $this->assertEquals($hidden_button_text[$key], $button->getText());
    }

    // Press buttons in the dialog to ensure there are no AJAX errors.
    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Hello world');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $has_focus_text = $this->getSession()->evaluateScript('document.activeElement.textContent');
    $this->assertEquals('Do it', $has_focus_text);
    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Preview');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $has_focus_text = $this->getSession()->evaluateScript('document.activeElement.textContent');
    $this->assertEquals('Do it', $has_focus_text);

    // Reset: close the form.
    $form_dialog->findButton('Close')->press();

    // Non AJAX version of Link 6.
    $this->drupalGet('admin/structure/contact/add');
    // Check we get a chunk of the code, we can't test the whole form as form
    // build id and token with be different.
    $this->assertSession()->elementExists('xpath', "//form[@id='contact-form-add-form']");

    // Reset: Return to the dialog links page.
    $this->drupalGet('ajax-test/dialog');

    $this->clickLink('Link 6 (entity form)');
    $dialog_add = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $this->assertNotNull($dialog_add, 'Form dialog is visible');

    $form_add = $dialog_add->find('css', 'form.contact-form-add-form');
    $this->assertNotNull($form_add, 'Modal dialog JSON contains entity form.');

    $form_title = $dialog_add->find('css', "h1.ui-dialog-title:contains('Add contact form')");
    $this->assertNotNull($form_title, 'The add form title is as expected.');

    // Test: dialog link opener with title callback.
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet("/ajax-test/link-page-dialog");
    $page->clickLink('Modal link');
    $this->assertEquals('Dialog link page title', $assert_session->waitForElement('css', '.ui-dialog-title')->getText());
  }

  /**
   * Tests dialog link opener with different HTTP methods.
   */
  public function testHttpMethod(): void {
    $assert = $this->assertSession();
    $script = <<<SCRIPT
      (function() {
        return document.querySelector('div[aria-describedby="drupal-modal"]').offsetWidth;
      }())
      SCRIPT;

    // Open the modal dialog with POST HTTP method.
    $this->drupalGet('/ajax-test/http-methods');
    $this->clickLink('Link');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Modal dialog contents');
    $width = $this->getSession()->getDriver()->evaluateScript($script);
    // The theme is adding 4px as padding and border on each side.
    $this->assertSame(808, $width);

    // Switch to GET HTTP method.
    // @see \Drupal\ajax_test\Controller\AjaxTestController::httpMethods()
    \Drupal::state()->set('ajax_test.http_method', 'GET');

    // Open the modal dialog with GET HTTP method.
    $this->drupalGet('/ajax-test/http-methods');
    $this->clickLink('Link');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Modal dialog contents');
    $width = $this->getSession()->getDriver()->evaluateScript($script);
    // The theme is adding 4px as padding and border on each side.
    $this->assertSame(808, $width);
  }

}
