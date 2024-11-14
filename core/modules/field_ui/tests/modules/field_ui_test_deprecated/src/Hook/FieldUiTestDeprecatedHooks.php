<?php

declare(strict_types=1);

namespace Drupal\field_ui_test_deprecated\Hook;

use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\Form\FieldStorageConfigEditForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_ui_test_deprecated.
 */
class FieldUiTestDeprecatedHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for field_storage_config_edit_form.
   */
  #[Hook('form_field_storage_config_edit_form_alter')]
  public function formFieldStorageConfigEditFormAlter(&$form, FormStateInterface $form_state) : void {
    if (!$form_state->getFormObject() instanceof FieldStorageConfigEditForm) {
      throw new \LogicException('field_storage_config_edit_form() expects to get access to the field storage config entity edit form.');
    }
    if (!$form_state->getFormObject()->getEntity() instanceof FieldStorageConfigInterface) {
      throw new \LogicException('field_storage_config_edit_form() expects to get access to the field storage config entity.');
    }
    if (!isset($form['cardinality_container']['cardinality'])) {
      throw new \LogicException('field_storage_config_edit_form() expects to that the cardinality container with the cardinality form element exists.');
    }
    $form['cardinality_container']['hello'] = ['#markup' => 'Greetings from the field_storage_config_edit_form() alter.'];
  }

}
