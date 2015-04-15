<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityFormInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines an interface for entity form classes.
 */
interface EntityFormInterface extends BaseFormIdInterface {

  /**
   * Sets the operation for this form.
   *
   * @param string $operation
   *   The name of the current operation.
   *
   * @return $this
   */
  public function setOperation($operation);

  /**
   * Returns the operation identifying the form.
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
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current form entity.
   */
  public function getEntity();

  /**
   * Sets the form entity.
   *
   * Sets the form entity which will be used for populating form element
   * defaults. Usually, the form entity gets updated by
   * \Drupal\Core\Entity\EntityFormInterface::submit(), however this may
   * be used to completely exchange the form entity, e.g. when preparing the
   * rebuild of a multi-step form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Determines which entity will be used by this form from a RouteMatch object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   The entity type identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object as determined from the passed-in route match.
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id);

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * For building the updated entity object the form's entity is cloned and
   * the submitted form values are copied to entity properties. The form's
   * entity remains unchanged.
   *
   * @see \Drupal\Core\Entity\EntityFormInterface::getEntity()
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An updated copy of the form's entity object.
   */
  public function buildEntity(array $form, FormStateInterface $form_state);

  /**
   * Validates the submitted form values of the entity form.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The built entity.
   */
  public function validate(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler for the 'save' action.
   *
   * Normally this method should be overridden to provide specific messages to
   * the user and redirect the form after the entity has been saved.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public function save(array $form, FormStateInterface $form_state);

  /**
   * Sets the string translation service for this form.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   *
   * @return $this
   */
  public function setStringTranslation(TranslationInterface $string_translation);

  /**
   * Sets the module handler for this form.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler);

  /**
   * Sets the entity manager for this form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   *
   * @return $this
   */
  public function setEntityManager(EntityManagerInterface $entity_manager);

}
