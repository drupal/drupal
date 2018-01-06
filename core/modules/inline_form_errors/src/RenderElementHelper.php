<?php

namespace Drupal\inline_form_errors;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides functionality to process render elements.
 */
class RenderElementHelper {

  /**
   * Alters the element type info.
   *
   * @param array $info
   *   An associative array with structure identical to that of the return value
   *   of \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
   */
  public function alterElementInfo(array &$info) {
    foreach ($info as $element_type => $element_info) {
      $info[$element_type]['#process'][] = [static::class, 'processElement'];
    }
  }

  /**
   * Process all render elements.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Prevent displaying inline form errors when disabled for the whole form.
    if (!empty($complete_form['#disable_inline_form_errors'])) {
      $element['#error_no_message'] = TRUE;
    }

    return $element;
  }

}
