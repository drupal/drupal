<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for uploading a file.
 *
 * If you add this element to a form the enctype="multipart/form-data" attribute
 * will automatically be added to the form element.
 *
 * Properties:
 * - #multiple: A Boolean indicating whether multiple files may be uploaded.
 * - #size: The size of the file input element in characters.
 *
 * @FormElement("file")
 */
class File extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => [
        [$class, 'processFile'],
      ],
      '#size' => 60,
      '#pre_render' => [
        [$class, 'preRenderFile'],
      ],
      '#theme' => 'input__file',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes a file upload element, make use of #multiple if present.
   */
  public static function processFile(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#multiple']) {
      $element['#attributes'] = ['multiple' => 'multiple'];
      $element['#name'] .= '[]';
    }
    return $element;
  }

  /**
   * Prepares a #type 'file' render element for input.html.twig.
   *
   * For assistance with handling the uploaded file correctly, see the API
   * provided by file.inc.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #name, #size, #description, #required,
   *   #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderFile($element) {
    $element['#attributes']['type'] = 'file';
    Element::setAttributes($element, ['id', 'name', 'size']);
    static::setAttributes($element, ['js-form-file', 'form-file']);

    return $element;
  }

}
