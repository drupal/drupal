<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Ajax image buttons work with key press events.
 *
 * @group Ajax
 */
class AjaxFormImageButtonTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_forms_test'];

  /**
   * Tests image buttons can be operated with the keyboard ENTER key.
   */
  public function testAjaxImageButtonKeypressEnter() {
    // Get a Field UI manage-display page.
    $this->drupalGet('ajax_forms_image_button_form');
    $assertSession = $this->assertSession();
    $session = $this->getSession();

    $button = $session->getPage()->findButton('Edit');
    $button->keyPress(13);

    $this->assertNotEmpty($assertSession->waitForElementVisible('css', '#ajax-1-more-div'), 'Page updated after image button pressed');
  }

  /**
   * Tests image buttons can be operated with the keyboard SPACE key.
   */
  public function testAjaxImageButtonKeypressSpace() {
    // Get a Field UI manage-display page.
    $this->drupalGet('ajax_forms_image_button_form');
    $assertSession = $this->assertSession();
    $session = $this->getSession();

    $button = $session->getPage()->findButton('Edit');
    $button->keyPress(32);

    $this->assertNotEmpty($assertSession->waitForElementVisible('css', '#ajax-1-more-div'), 'Page updated after image button pressed');
  }

}
