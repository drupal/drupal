<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityFormControllerNG.
 */

namespace Drupal\Core\Entity;

/**
 * Entity form controller variant for entity types using the new property API.
 *
 * @todo: Merge with EntityFormController and overhaul once all entity types
 * are converted to the new property API.
 */
class EntityFormControllerNG extends EntityFormController {

  /**
   * Overrides EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $entity) {
    // @todo Exploit the Field API to generate the default widgets for the
    // entity properties.
    $info = $entity->entityInfo();
    if (!empty($info['fieldable'])) {
      $entity->setCompatibilityMode(TRUE);
      field_attach_form($entity->entityType(), $entity, $form, $form_state, $this->getFormLangcode($form_state));
      $entity->setCompatibilityMode(FALSE);
    }
    return $form;
  }

  /**
   * Overrides EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    // @todo Exploit the Field API to validate the values submitted for the
    // entity properties.
    $entity = $this->buildEntity($form, $form_state);
    $info = $entity->entityInfo();

    if (!empty($info['fieldable'])) {
      $entity->setCompatibilityMode(TRUE);
      field_attach_form_validate($entity->entityType(), $entity, $form, $form_state);
      $entity->setCompatibilityMode(FALSE);
    }

    // @todo Remove this.
    // Execute legacy global validation handlers.
    unset($form_state['validate_handlers']);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * Overrides EntityFormController::buildEntity().
   */
  public function buildEntity(array $form, array &$form_state) {
    $entity = clone $this->getEntity($form_state);
    $entity_type = $entity->entityType();
    $info = entity_get_info($entity_type);
    // @todo Exploit the Field API to process the submitted entity field.

    // Copy top-level form values that are not for fields to entity properties,
    // without changing existing entity properties that are not being edited by
    // this form. Copying field values must be done using field_attach_submit().
    $values_excluding_fields = $info['fieldable'] ? array_diff_key($form_state['values'], field_info_instances($entity_type, $entity->bundle())) : $form_state['values'];
    $translation = $entity->getTranslation($this->getFormLangcode($form_state), FALSE);
    $definitions = $translation->getPropertyDefinitions();
    foreach ($values_excluding_fields as $key => $value) {
      if (isset($definitions[$key])) {
        $translation->$key = $value;
      }
    }

    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity_type, $entity, &$form, &$form_state));
      }
    }

    // Copy field values to the entity.
    if ($info['fieldable']) {
      $entity->setCompatibilityMode(TRUE);
      field_attach_submit($entity_type, $entity, $form, $form_state);
      $entity->setCompatibilityMode(FALSE);
    }
    return $entity;
  }
}
