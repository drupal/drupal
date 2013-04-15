<?php

/**
 * @file
 * Contains \Drupal\filter\Form\DisableForm.
 */

namespace Drupal\filter\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\filter\Plugin\Core\Entity\FilterFormat;

/**
 * Provides the filter format disable form.
 */
class DisableForm extends ConfirmFormBase {

  /**
   * The format being disabled.
   *
   * @var \Drupal\filter\Plugin\Core\Entity\FilterFormat
   */
  protected $format;

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'filter_admin_disable';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  protected function getQuestion() {
    return t('Are you sure you want to disable the text format %format?', array('%format' => $this->format->name));
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getCancelPath().
   */
  protected function getCancelPath() {
    return 'admin/config/content/formats';
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  public function getConfirmText() {
    return t('Disable');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getDescription().
   */
  public function getDescription() {
    return t('Disabled text formats are completely removed from the administrative interface, and any content stored with that format will not be displayed. This action cannot be undone.');
  }

  /**
   * Overrides \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, FilterFormat $filter_format = NULL) {
    $this->format = $filter_format;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->format->disable()->save();
    drupal_set_message(t('Disabled text format %format.', array('%format' => $this->format->name)));

    $form_state['redirect'] = 'admin/config/content/formats';
  }

}
