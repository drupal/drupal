<?php

/**
 * @file
 * Definition of Drupal\entity\EntityFormControllerInterface.
 */

namespace Drupal\entity;

/**
 * Defines a common interface for entity form controller classes.
 */
interface EntityFormControllerInterface {

  /**
   * Constructs the object.
   *
   * @param string $operation
   *   The name of the current operation.
   */
  public function __construct($operation);

  /**
   * Builds an entity form.
   *
   * This is the entity form builder which is invoked via drupal_build_form()
   * to retrieve the form.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param string $entity_type
   *   The type of the entity being edited.
   * @param \Drupal\entity\EntityInterface $entity
   *   The entity being edited.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function build(array $form, array &$form_state, EntityInterface $entity);

  /**
   * Returns the code identifying the active form language.
   *
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return string
   *   The form language code.
   */
  public function getFormLangcode($form_state);

  /**
   * Returns the operation identifying the form controller.
   *
   * @return string
   *   The name of the operation.
   */
  public function getOperation();

  /**
   * Returns the form entity.
   *
   * The form entity which has been used for populating form element defaults.
   *
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Drupal\entity\EntityInterface
   *   The current form entity.
   */
  public function getEntity(array $form_state);

  /**
   * Sets the form entity.
   *
   * Sets the form entity which will be used for populating form element
   * defaults. Usually, the form entity gets updated by
   * \Drupal\entity\EntityFormControllerInterface::submit(), however this may
   * be used to completely exchange the form entity, e.g. when preparing the
   * rebuild of a multi-step form.
   *
   * @param \Drupal\entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function setEntity(EntityInterface $entity, array &$form_state);

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * For building the updated entity object the form's entity is cloned and
   * the submitted form values are copied to entity properties. The form's
   * entity remains unchanged.
   *
   * @see \Drupal\entity\EntityFormControllerInterface::getEntity()
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Drupal\entity\EntityInterface
   *   An updated copy of the form's entity object.
   */
  public function buildEntity(array $form, array &$form_state);

  /**
   * Validates the submitted form values of the entity form.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, array &$form_state);

  /**
   * Updates the form's entity by processing this submission's values.
   *
   * Note: Before this can be safely invoked the entity form must have passed
   * validation, i.e. only add this as form #submit handler if validation is
   * added as well.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submit(array $form, array &$form_state);

}
