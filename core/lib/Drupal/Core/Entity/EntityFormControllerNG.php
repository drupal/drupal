<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormControllerNG.
 */

namespace Drupal\Core\Entity;

/**
 * Entity form controller variant for entity types using the new property API.
 *
 * @todo: Merge with EntityFormController and overhaul once all entity types
 * are converted to the new entity field API.
 *
 * See the EntityNG documentation for an explanation of "NG".
 *
 * @see \Drupal\Core\EntityNG
 */
class EntityFormControllerNG extends EntityFormController {

  /**
   * Overrides EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    // @todo Exploit the Field API to generate the default widgets for the
    // entity fields.
    $info = $entity->entityInfo();
    if (!empty($info['fieldable'])) {
      field_attach_form($entity, $form, $form_state, $this->getFormLangcode($form_state));
    }

    // Assign the weights configured in the form display.
    foreach ($this->getFormDisplay($form_state)->getComponents() as $name => $options) {
      if (isset($form[$name])) {
        $form[$name]['#weight'] = $options['weight'];
      }
    }

    return $form;
  }

  /**
   * Overrides EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    // @todo Exploit the Field API to validate the values submitted for the
    // entity fields.
    $entity = $this->buildEntity($form, $form_state);
    $info = $entity->entityInfo();

    if (!empty($info['fieldable'])) {
      field_attach_form_validate($entity, $form, $form_state);
    }

    // @todo Remove this.
    // Execute legacy global validation handlers.
    unset($form_state['validate_handlers']);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * Overrides EntityFormController::submitEntityLanguage().
   */
  protected function submitEntityLanguage(array $form, array &$form_state) {
    // Nothing to do here, as original field values are always stored with
    // Language::LANGCODE_DEFAULT language.
    // @todo Delete this method when merging EntityFormControllerNG with
    //   EntityFormController.
  }

  /**
   * Overrides EntityFormController::buildEntity().
   */
  public function buildEntity(array $form, array &$form_state) {
    $entity = clone $this->entity;
    $entity_type = $entity->entityType();
    $info = entity_get_info($entity_type);
    // @todo Exploit the Field API to process the submitted entity fields.

    // Copy top-level form values that are entity fields but not handled by
    // field API without changing existing entity fields that are not being
    // edited by this form. Values of fields handled by field API are copied
    // by field_attach_extract_form_values() below.
    $values_excluding_fields = $info['fieldable'] ? array_diff_key($form_state['values'], field_info_instances($entity_type, $entity->bundle())) : $form_state['values'];
    $translation = $entity->getTranslation($this->getFormLangcode($form_state), FALSE);
    $definitions = $translation->getPropertyDefinitions();
    foreach ($values_excluding_fields as $key => $value) {
      if (isset($definitions[$key])) {
        $translation->$key = $value;
      }
    }

    // Invoke all specified builders for copying form values to entity fields.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity_type, $entity, &$form, &$form_state));
      }
    }

    // Invoke field API for copying field values.
    if ($info['fieldable']) {
      field_attach_extract_form_values($entity, $form, $form_state);
    }
    return $entity;
  }
}
