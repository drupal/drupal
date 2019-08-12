<?php

namespace Drupal\Tests\ckeditor\Traits;

/**
 * Provides methods to test CKEditor.
 *
 * This trait is meant to be used only by functional JavaScript test classes.
 */
trait CKEditorTestTrait {

  /**
   * Waits for CKEditor to initialize.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitForEditor($instance_id = 'edit-body-0-value', $timeout = 10000) {
    $condition = <<<JS
      (function() {
        return (
          typeof CKEDITOR !== 'undefined'
          && typeof CKEDITOR.instances["$instance_id"] !== 'undefined'
          && CKEDITOR.instances["$instance_id"].instanceReady
        );
      }());
JS;

    $this->getSession()->wait($timeout, $condition);
  }

  /**
   * Assigns a name to the CKEditor iframe.
   *
   * @see \Behat\Mink\Session::switchToIFrame()
   */
  protected function assignNameToCkeditorIframe() {
    $javascript = <<<JS
(function(){
  document.getElementsByClassName('cke_wysiwyg_frame')[0].id = 'ckeditor';
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Clicks a CKEditor button.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function pressEditorButton($name) {
    $this->getEditorButton($name)->click();
  }

  /**
   * Waits for a CKEditor button and returns it when available and visible.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  protected function getEditorButton($name) {
    $this->getSession()->switchToIFrame();
    $button = $this->assertSession()->waitForElementVisible('css', 'a.cke_button__' . $name);
    $this->assertNotEmpty($button);

    return $button;
  }

  /**
   * Asserts a CKEditor button is disabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function assertEditorButtonDisabled($name) {
    $button = $this->getEditorButton($name);
    $this->assertTrue($button->hasClass('cke_button_disabled'));
    $this->assertSame('true', $button->getAttribute('aria-disabled'));
  }

  /**
   * Asserts a CKEditor button is enabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function assertEditorButtonEnabled($name) {
    $button = $this->getEditorButton($name);
    $this->assertFalse($button->hasClass('cke_button_disabled'));
    $this->assertSame('false', $button->getAttribute('aria-disabled'));
  }

}
