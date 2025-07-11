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
    _inline_form_errors_set_errors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for details element templates.
   */
  #[Hook('preprocess_details')]
  public function preprocessDetails(&$variables): void {
    _inline_form_errors_set_errors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for fieldset element templates.
   */
  #[Hook('preprocess_fieldset')]
  public function preprocessFieldset(&$variables): void {
    _inline_form_errors_set_errors($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for datetime form wrapper templates.
   */
  #[Hook('preprocess_datetime_wrapper')]
  public function preprocessDatetimeWrapper(&$variables): void {
    _inline_form_errors_set_errors($variables);
  }

}
