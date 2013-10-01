<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityFormController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;

/**
 * Entity form controller variant for content entity types.
 *
 * @see \Drupal\Core\ContentEntityBase
 */
class ContentEntityFormController extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    // @todo Exploit the Field API to generate the default widgets for the
    // entity fields.
    $info = $entity->entityInfo();
    if (!empty($info['fieldable'])) {
      field_attach_form($entity, $form, $form_state, $this->getFormLangcode($form_state));
    }

    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = array($this, 'processForm');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $entity_type = $entity->entityType();
    $entity_langcode = $entity->language()->id;

    $violations = array();
    foreach ($entity as $field_name => $field) {
      $field_violations = $field->validate();
      if (count($field_violations)) {
        $violations[$field_name] = $field_violations;
      }
    }

    // Map errors back to form elements.
    if ($violations) {
      foreach ($violations as $field_name => $field_violations) {
        $langcode = field_is_translatable($entity_type, field_info_field($entity_type, $field_name)) ? $entity_langcode : Language::LANGCODE_NOT_SPECIFIED;
        $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);
        $field_state['constraint_violations'] = $field_violations;
        field_form_set_state($form['#parents'], $field_name, $form_state, $field_state);
      }

      field_invoke_method('flagErrors', _field_invoke_widget_target($form_state['form_display']), $entity, $form, $form_state);
    }

    // @todo Remove this.
    // Execute legacy global validation handlers.
    unset($form_state['validate_handlers']);
    form_execute_handlers('validate', $form, $form_state);
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(array &$form_state) {
    // Ensure we act on the translation object corresponding to the current form
    // language.
    $this->entity = $this->getTranslatedEntity($form_state);
    parent::init($form_state);
  }

  /**
   * Returns the translation object corresponding to the form language.
   *
   * @param array $form_state
   *   A keyed array containing the current state of the form.
   */
  protected function getTranslatedEntity(array $form_state) {
    $langcode = $this->getFormLangcode($form_state);
    $translation = $this->entity->getTranslation($langcode);
    // Ensure that the entity object is a BC entity if the original one is.
    return $this->entity instanceof EntityBCDecorator ? $translation->getBCEntity() : $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(array $form_state) {
    $entity = $this->entity;
    if (!empty($form_state['langcode'])) {
      $langcode = $form_state['langcode'];
    }
    else {
      // If no form langcode was provided we default to the current content
      // language and inspect existing translations to find a valid fallback,
      // if any.
      $translations = $entity->getTranslationLanguages();
      $languageManager = \Drupal::languageManager();
      $langcode = $languageManager->getLanguage(Language::TYPE_CONTENT)->id;
      $fallback = $languageManager->isMultilingual() ? language_fallback_get_candidates() : array();
      while (!empty($langcode) && !isset($translations[$langcode])) {
        $langcode = array_shift($fallback);
      }
    }

    // If the site is not multilingual or no translation for the given form
    // language is available, fall back to the entity language.
    if (!empty($langcode))  {
      return $langcode;
    }
    else {
      // If the entity is translatable, return the original language.
      return $entity->getUntranslated()->language()->id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultFormLangcode(array $form_state) {
    return $this->getFormLangcode($form_state) == $this->entity->getUntranslated()->language()->id;
  }

  /**
   * {@inheritdoc}
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
    $definitions = $entity->getPropertyDefinitions();
    foreach ($values_excluding_fields as $key => $value) {
      if (isset($definitions[$key])) {
        $entity->$key = $value;
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
      field_attach_extract_form_values($entity, $form, $form_state, array('langcode' => $this->getFormLangcode($form_state)));
    }
    return $entity;
  }
}
