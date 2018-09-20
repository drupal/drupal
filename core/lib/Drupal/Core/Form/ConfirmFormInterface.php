<?php

namespace Drupal\Core\Form;

/**
 * Defines the behavior a confirmation form.
 */
interface ConfirmFormInterface extends FormInterface {

  /**
   * Returns the question to ask the user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion();

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl();

  /**
   * Returns additional text to display as a description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form description.
   */
  public function getDescription();

  /**
   * Returns a caption for the button that confirms the action.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form confirmation text.
   */
  public function getConfirmText();

  /**
   * Returns a caption for the link which cancels the action.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
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
