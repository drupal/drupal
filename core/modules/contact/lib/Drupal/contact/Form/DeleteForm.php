<?php

/**
 * @file
 * Contains \Drupal\contact\Form\DeleteForm.
 */

namespace Drupal\contact\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\contact\Plugin\Core\Entity\Category;

/**
 * Builds the form to delete a contact category.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The contact category being deleted.
   *
   * @var \Drupal\contact\Plugin\Core\Entity\Category
   */
  protected $contactCategory;

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'contact_category_delete_form';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete %name?', array('%name' => $this->contactCategory->label()));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/structure/contact';
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::buildForm().
   */
  public function buildForm(array $form, array &$form_state, Category $contact_category = NULL) {
    $this->contactCategory = $contact_category;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->contactCategory->delete();
    drupal_set_message(t('Category %label has been deleted.', array('%label' => $this->contactCategory->label())));
    watchdog('contact', 'Category %label has been deleted.', array('%label' => $this->contactCategory->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/contact';
  }

}
