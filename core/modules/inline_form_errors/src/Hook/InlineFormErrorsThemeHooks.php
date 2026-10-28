<?php

namespace Drupal\inline_form_errors\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for inline_form_errors.
 */
class InlineFormErrorsThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'inline_form_errors_summary' => [
        'variables' => [
          'error_links' => [],
          'form_id' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_inline_form_errors_summary')]
  public function themeSuggestionsInlineFormErrorsSummary(array $variables): array {
    $suggestions = [];
    if (!empty($variables['form_id'])) {
      $suggestions[] = 'inline_form_errors_summary__' . $variables['form_id'];
    }
    return $suggestions;
  }

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
