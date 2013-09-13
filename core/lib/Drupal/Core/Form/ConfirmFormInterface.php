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
   * Returns the route to go to if the user cancels the action.
   *
   * @return array
   *   An associative array with the following keys:
   *   - route_name: The name of the route.
   *   - route_parameters: (optional) An associative array of parameter names
   *     and values.
   *   - options: (optional) An associative array of additional options. See
   *     \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *     comprehensive documentation.
   */
  public function getCancelRoute();

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
