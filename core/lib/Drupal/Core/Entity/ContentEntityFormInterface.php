<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a common interface for content entity form classes.
 */
interface ContentEntityFormInterface extends EntityFormInterface {

  /**
   * Gets the form display.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface.
   *   The current form display.
   */
  public function getFormDisplay(FormStateInterface $form_state);

  /**
   * Sets the form display.
   *
   * Sets the form display which will be used for populating form element
   * defaults.
   *
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
   *   The form display that the current form operates with.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return $this
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state);

  /**
   * Gets the code identifying the active form language.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The form language code.
   */
  public function getFormLangcode(FormStateInterface $form_state);

  /**
   * Checks whether the current form language matches the entity one.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Returns TRUE if the entity form language matches the entity one.
   */
  public function isDefaultFormLangcode(FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   *
   * Note that extending classes should not override this method to add entity
   * validation logic, but define further validation constraints using the
   * entity validation API and/or provide a new validation constraint if
   * necessary. This is the only way to ensure that the validation logic
   * is correctly applied independently of form submissions; e.g., for REST
   * requests.
   * For more information about entity validation, see
   * https://www.drupal.org/node/2015613.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The built entity.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

}
