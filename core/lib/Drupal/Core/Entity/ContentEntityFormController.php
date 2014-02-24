<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityFormController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form controller variant for content entity types.
 *
 * @see \Drupal\Core\ContentEntityBase
 */
class ContentEntityFormController extends EntityFormController {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContentEntityFormController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    // @todo Exploit the Field API to generate the default widgets for the
    // entity fields.
    if ($entity->getEntityType()->isFieldable()) {
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
    $this->updateFormLangcode($form_state);
    $entity = $this->buildEntity($form, $form_state);
    $entity_type = $entity->getEntityTypeId();
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
    $langcode = $this->getFormLangcode($form_state);
    $this->entity = $this->entity->getTranslation($langcode);
    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormLangcode(array &$form_state) {
    if (empty($form_state['langcode'])) {
      // Imply a 'view' operation to ensure users edit entities in the same
      // language they are displayed. This allows to keep contextual editing
      // working also for multilingual entities.
      $form_state['langcode'] = $this->entityManager->getTranslationFromContext($this->entity)->language()->id;
    }
    return $form_state['langcode'];
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
    $entity_type_id = $entity->getEntityTypeId();
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);

    // @todo Exploit the Entity Field API to process the submitted field values.
    // Copy top-level form values that are entity fields but not handled by
    // field API without changing existing entity fields that are not being
    // edited by this form. Values of fields handled by field API are copied
    // by field_attach_extract_form_values() below.
    $values_excluding_fields = $entity_type->isFieldable() ? array_diff_key($form_state['values'], field_info_instances($entity_type_id, $entity->bundle())) : $form_state['values'];
    $definitions = $entity->getFieldDefinitions();

    foreach ($values_excluding_fields as $key => $value) {
      if (isset($definitions[$key])) {
        $entity->$key = $value;
      }
    }

    // Invoke all specified builders for copying form values to entity fields.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity_type_id, $entity, &$form, &$form_state));
      }
    }

    // Invoke field API for copying field values.
    if ($entity_type->isFieldable()) {
      field_attach_extract_form_values($entity, $form, $form_state, array('langcode' => $this->getFormLangcode($form_state)));
    }
    return $entity;
  }

}
