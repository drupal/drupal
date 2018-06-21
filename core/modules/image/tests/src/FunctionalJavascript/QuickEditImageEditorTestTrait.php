<?php

namespace Drupal\Tests\image\FunctionalJavascript;

/**
 * @see \Drupal\image\Plugin\InPlaceEditor\Image
 * @see \Drupal\Tests\quickedit\FunctionalJavascript\QuickEditJavascriptTestBase
 */
trait QuickEditImageEditorTestTrait {

  /**
   * Awaits the 'image' in-place editor.
   */
  protected function awaitImageEditor() {
    $this->assertJsCondition('document.querySelector(".quickedit-image-field-info") !== null', 10000);

    $quickedit_entity_toolbar = $this->getSession()->getPage()->findById('quickedit-entity-toolbar');
    $this->assertNotNull($quickedit_entity_toolbar->find('css', 'form.quickedit-image-field-info input[name="alt"]'));
  }

  /**
   * Simulates typing in the 'image' in-place editor 'alt' attribute text input.
   *
   * @param string $text
   *   The text to type.
   */
  protected function typeInImageEditorAltTextInput($text) {
    $quickedit_entity_toolbar = $this->getSession()->getPage()->findById('quickedit-entity-toolbar');
    $input = $quickedit_entity_toolbar->find('css', 'form.quickedit-image-field-info input[name="alt"]');
    $input->setValue($text);
  }

  /**
   * Simulates dragging and dropping an image on the 'image' in-place editor.
   *
   * @param string $file_uri
   *   The URI of the image file to drag and drop.
   */
  protected function dropImageOnImageEditor($file_uri) {
    // Our headless browser can't drag+drop files, but we can mock the event.
    // Append a hidden upload element to the DOM.
    $script = 'jQuery("<input id=\"quickedit-image-test-input\" type=\"file\" />").appendTo("body")';
    $this->getSession()->executeScript($script);

    // Find the element, and set its value to our new image.
    $input = $this->assertSession()->elementExists('css', '#quickedit-image-test-input');
    $filepath = $this->container->get('file_system')->realpath($file_uri);
    $input->attachFile($filepath);

    // Trigger the upload logic with a mock "drop" event.
    $script = 'var e = jQuery.Event("drop");'
      . 'e.originalEvent = {dataTransfer: {files: jQuery("#quickedit-image-test-input").get(0).files}};'
      . 'e.preventDefault = e.stopPropagation = function () {};'
      . 'jQuery(".quickedit-image-dropzone").trigger(e);';
    $this->getSession()->executeScript($script);

    // Wait for the dropzone element to be removed (i.e. loading is done).
    $js_condition = <<<JS
function () {
  var activeFieldID = Drupal.quickedit.collections.entities
    .findWhere({state:'opened'})
    .get('fields')
    .filter(function (fieldModel) {
      var state = fieldModel.get('state');
        return state === 'active' || state === 'changed';
    })[0]
    .get('fieldID')
  return document.querySelector('[data-quickedit-field-id="' + activeFieldID + '"] .quickedit-image-dropzone') === null;
}();
JS;

    $this->assertJsCondition($js_condition, 20000);

  }

}
