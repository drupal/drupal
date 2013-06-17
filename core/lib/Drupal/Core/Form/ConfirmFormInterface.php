<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfirmFormInterface.
 */

namespace Drupal\Core\Form;

/**
 * Defines the behavior a confirmation form.
 */
interface ConfirmFormInterface extends FormInterface {

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion();

  /**
   * Returns the page to go to if the user cancels the action.
   *
   * @return string|array
   *   This can be either:
   *   - A string containing a Drupal path.
   *   - An associative array with a 'path' key. Additional array values are
   *     passed as the $options parameter to l().
   *   If the 'destination' query parameter is set in the URL when viewing a
   *   confirmation form, that value will be used instead of this path.
   */
  public function getCancelPath();

  /**
   * Returns additional text to display as a description.
   *
   * @return string
   *   The form description.
   */
  public function getDescription();

  /**
   * Returns a caption for the button that confirms the action.
   *
   * @return string
   *   The form confirmation text.
   */
  public function getConfirmText();

  /**
   * Returns a caption for the link which cancels the action.
   *
   * @return string
   *   The form cancellation text.
   */
  public function getCancelText();

  /**
   * Returns the internal name used to refer to the confirmation item.
   *
   * @return string
   *   The internal form name.
   */
  public function getFormName();

}
