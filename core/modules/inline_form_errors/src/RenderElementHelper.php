<?php

namespace Drupal\inline_form_errors;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides functionality to process render elements.
 */
class RenderElementHelper implements TrustedCallbackInterface {

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
   * Implements #process callback for ::alterElementInfo.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Prevent displaying inline form errors when disabled for the whole form.
    if (!empty($complete_form['#disable_inline_form_errors'])) {
      $element['#error_no_message'] = TRUE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processElement'];
  }

}
