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
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitForEditor($instance_id = 'edit-body-0-value', $timeout = 10000) {
    $condition = <<<JS
      (function() {
        return (
          typeof CKEDITOR !== 'undefined'
          && typeof CKEDITOR.instances["{$instance_id}"] !== 'undefined'
          && CKEDITOR.instances["{$instance_id}"].instanceReady
        );
      }())
JS;
    $this->assertJsCondition($condition, $timeout);
  }

  /**
   * Assigns a name to the CKEditor iframe.
   *
   * @param string $id
   *   (optional) The id to assign the iframe element. Defaults to 'ckeditor'.
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   *
   * @see \Behat\Mink\Session::switchToIFrame()
   */
  protected function assignNameToCkeditorIframe($id = 'ckeditor', $instance_id = 'edit-body-0-value') {
    $javascript = <<<JS
(function(){
  CKEDITOR.instances['{$instance_id}'].element.getParent().find('.cke_wysiwyg_frame').$[0].id = '{$id}';
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Clicks a CKEditor button.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   */
  protected function pressEditorButton($name, $instance_id = 'edit-body-0-value') {
    $this->getEditorButton($name, $instance_id)->click();
  }

  /**
   * Waits for a CKEditor button and returns it when available and visible.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  protected function getEditorButton($name, $instance_id = 'edit-body-0-value') {
    $this->getSession()->switchToIFrame();
    $button = $this->assertSession()->waitForElementVisible('css', "#cke_$instance_id a.cke_button__" . $name);
    $this->assertNotEmpty($button);

    return $button;
  }

  /**
   * Asserts a CKEditor button is disabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   */
  protected function assertEditorButtonDisabled($name, $instance_id = 'edit-body-0-value') {
    $button = $this->getEditorButton($name, $instance_id);
    $this->assertTrue($button->hasClass('cke_button_disabled'));
    $this->assertSame('true', $button->getAttribute('aria-disabled'));
  }

  /**
   * Asserts a CKEditor button is enabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   * @param string $instance_id
   *   (optional) The CKEditor instance ID. Defaults to 'edit-body-0-value'.
   */
  protected function assertEditorButtonEnabled($name, $instance_id = 'edit-body-0-value') {
    $button = $this->getEditorButton($name, $instance_id);
    $this->assertFalse($button->hasClass('cke_button_disabled'));
    $this->assertSame('false', $button->getAttribute('aria-disabled'));
  }

}
