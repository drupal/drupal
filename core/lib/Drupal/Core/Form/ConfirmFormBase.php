<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfirmFormBase.
 */

namespace Drupal\Core\Form;

/**
 * Provides an generic base class for a confirmation form.
 */
abstract class ConfirmFormBase implements FormInterface {

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  abstract protected function getQuestion();

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
  abstract protected function getCancelPath();

  /**
   * Returns additional text to display as a description.
   *
   * @return string
   *   The form description.
   */
  protected function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * Returns a caption for the button that confirms the action.
   *
   * @return string
   *   The form confirmation text.
   */
  protected function getConfirmText() {
    return t('Confirm');
  }

  /**
   * Returns a caption for the link which cancels the action.
   *
   * @return string
   *   The form cancellation text.
   */
  protected function getCancelText() {
    return t('Cancel');
  }

  /**
   * Returns the internal name used to refer to the confirmation item.
   *
   * @return string
   *   The internal form name.
   */
  protected function getFormName() {
    return 'confirm';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $path = $this->getCancelPath();
    // Prepare cancel link.
    if (isset($_GET['destination'])) {
      $options = drupal_parse_url($_GET['destination']);
    }
    elseif (is_array($path)) {
      $options = $path;
    }
    else {
      $options = array('path' => $path);
    }

    drupal_set_title($this->getQuestion(), PASS_THROUGH);

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = array('#markup' => $this->getDescription());
    $form[$this->getFormName()] = array('#type' => 'hidden', '#value' => 1);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->getConfirmText(),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->getCancelText(),
      '#href' => $options['path'],
      '#options' => $options,
    );
    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

}
