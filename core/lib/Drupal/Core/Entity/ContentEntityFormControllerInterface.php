<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityFormControllerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;

/**
 * Defines a common interface for content entity form controller classes.
 */
interface ContentEntityFormControllerInterface extends EntityFormControllerInterface {

  /**
   * Returns the form display.
   *
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface.
   *   The current form display.
   */
  public function getFormDisplay(array $form_state);

  /**
   * Sets the form display.
   *
   * Sets the form display which will be used for populating form element
   * defaults.
   *
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
   *   The form display that the current form operates with.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function setFormDisplay(EntityFormDisplayInterface $form_display, array &$form_state);

}
