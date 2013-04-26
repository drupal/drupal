<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\DeleteForm.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\views\ViewStorageInterface;

/**
 * Builds the form to delete a view.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The view being deleted.
   *
   * @var \Drupal\views\ViewStorageInterface
   */
  protected $view;

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the %name view?', array('%name' => $this->view->label()));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/structure/views';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'views_ui_confirm_delete';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, ViewStorageInterface $view = NULL) {
    $this->view = $view;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->view->delete();
    $form_state['redirect'] = 'admin/structure/views';
  }

}
