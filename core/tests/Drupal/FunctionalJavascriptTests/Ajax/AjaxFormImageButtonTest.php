<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the Ajax image buttons work with key press events.
 *
 * @group Ajax
 */
class AjaxFormImageButtonTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_forms_test'];

  /**
   * Tests image buttons can be operated with the keyboard ENTER key.
   */
  public function testAjaxImageButton() {
    // Get a Field UI manage-display page.
    $this->drupalGet('ajax_forms_image_button_form');
    $assertSession = $this->assertSession();
    $session = $this->getSession();

    $enter_key_event = <<<JS
jQuery('#edit-image-button')
  .trigger(jQuery.Event('keypress', {
    which: 13
  }));
JS;
    // PhantomJS driver has buggy behavior with key events, we send a JavaScript
    // key event instead.
    // @todo: use WebDriver event when we remove PhantomJS driver.
    $session->executeScript($enter_key_event);

    $this->assertNotEmpty($assertSession->waitForElementVisible('css', '#ajax-1-more-div'), 'Page updated after image button pressed');
  }

}
