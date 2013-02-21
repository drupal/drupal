<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\DeleteForm.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\views\ViewStorageInterface;

/**
 * Builds the form to delete a view.
 */
class DeleteForm implements FormInterface {

  /**
   * Creates a new instance of this form.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   The view being acted upon.
   *
   * @return array
   *   The built form array.
   */
  public function getForm(ViewStorageInterface $view) {
    return drupal_get_form($this, $view);
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
    $form_state['view'] = $view;
    return confirm_form($form,
      t('Are you sure you want to delete the %name view?', array('%name' => $view->getHumanName())),
      'admin/structure/views',
      t('This action cannot be undone.'),
      t('Delete'),
      t('Cancel')
    );
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
    $form_state['view']->delete();
    $form_state['redirect'] = 'admin/structure/views';
  }

}
