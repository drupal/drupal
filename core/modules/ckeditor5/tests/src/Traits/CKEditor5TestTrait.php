<?php

namespace Drupal\Tests\ckeditor5\Traits;

use Behat\Mink\Element\NodeElement;
use Drupal\Component\Utility\Html;

// cspell:ignore downcasted

/**
 * Provides methods to test CKEditor 5.
 *
 * This trait is meant to be used only by functional JavaScript test classes.
 */
trait CKEditor5TestTrait {

  /**
   * Gets CKEditor 5 instance data as a PHP DOMDocument.
   *
   * @return \DOMDocument
   *   The result of parsing CKEditor 5's data into a PHP DOMDocument.
   */
  protected function getEditorDataAsDom(): \DOMDocument {
    return Html::load($this->getEditorDataAsHtmlString());
  }

  /**
   * Gets CKEditor 5 instance data as a HTML string.
   *
   * @return string
   *   The result of retrieving CKEditor 5's data.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/api/module_editor-classic_classiceditor-ClassicEditor.html#function-getData
   */
  protected function getEditorDataAsHtmlString(): string {
    // We cannot trust on CKEditor updating the textarea every time model
    // changes. Therefore, the most reliable way to get downcasted data is to
    // use the CKEditor API.
    $javascript = <<<JS
(function(){
  return Drupal.CKEditor5Instances.get(Drupal.CKEditor5Instances.keys().next().value).getData();
})();
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Waits for CKEditor to initialize.
   */
  protected function waitForEditor() {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
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
    $button = $this->assertSession()->waitForElementVisible('xpath', "//button[span[text()='$name']]");
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
    $this->assertTrue($button->hasAttribute('aria-disabled'));
    $this->assertTrue($button->hasClass('ck-disabled'));
  }

  /**
   * Asserts a CKEditor button is enabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function assertEditorButtonEnabled($name) {
    $button = $this->getEditorButton($name);
    $this->assertFalse($button->hasAttribute('aria-disabled'));
    $this->assertFalse($button->hasClass('ck-disabled'));
  }

  /**
   * Asserts a particular balloon is visible.
   *
   * @param string $balloon_content_selector
   *   A CSS selector.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The asserted balloon.
   */
  protected function assertVisibleBalloon(string $balloon_content_selector): NodeElement {
    $this->assertSession()->elementExists('css', '.ck-balloon-panel_visible');
    $selector = ".ck-balloon-panel_visible .ck-balloon-rotator__content > .ck$balloon_content_selector";
    $this->assertSession()->elementExists('css', $selector);
    return $this->getSession()->getPage()->find('css', $selector);
  }

  /**
   * Gets a button from the currently visible balloon.
   *
   * @param string $name
   *   The label of the button to find.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The requested button.
   */
  protected function getBalloonButton(string $name): NodeElement {
    $button = $this->getSession()->getPage()
      ->find('css', '.ck-balloon-panel_visible .ck-balloon-rotator__content')
      ->find('xpath', "//button[span[text()='$name']]");
    $this->assertNotEmpty($button);
    return $button;
  }

}
