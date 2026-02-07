<?php

namespace Drupal\inline_form_errors\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for inline_form_errors.
 */
class InlineFormErrorsThemeHooks {
  /**
   * @file
   */

  /**
   * Implements hook_preprocess_HOOK() for form element templates.
   */
  #[Hook('preprocess_form_element')]
  public function preprocessFormElement(&$variables): void {
    $this->setErrors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for details element templates.
   */
  #[Hook('preprocess_details')]
  public function preprocessDetails(&$variables): void {
    $this->setErrors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for fieldset element templates.
   */
  #[Hook('preprocess_fieldset')]
  public function preprocessFieldset(&$variables): void {
    $this->setErrors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for datetime form wrapper templates.
   */
  #[Hook('preprocess_datetime_wrapper')]
  public function preprocessDatetimeWrapper(&$variables): void {
    $this->setErrors($variables);
  }

  /**
   * Populates form errors in the template.
   */
  protected function setErrors(&$variables): void {
    $element = $variables['element'];
    if (!empty($element['#errors']) && empty($element['#error_no_message'])) {
      $variables['errors'] = $element['#errors'];
    }
  }

}
