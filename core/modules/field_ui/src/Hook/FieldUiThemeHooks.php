<?php

namespace Drupal\field_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_ui.
 */
class FieldUiThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_form_element__new_storage_type')]
  public function preprocessFormElementNewStorageType(&$variables): void {
    // Add support for a variant string so radios in the add field form can be
    // programmatically distinguished.
    $variables['variant'] = $variables['element']['#variant'] ?? NULL;
  }

}
