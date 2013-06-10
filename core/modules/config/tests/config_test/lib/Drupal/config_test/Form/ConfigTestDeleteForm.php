<?php
/**
 * @file
 * Contains \Drupal\config_test\Form\ConfigTestDeleteForm.
 */

namespace Drupal\config_test\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\config_test\ConfigTestInterface;

/**
 * Delete confirmation form for config_test entities.
 */
class ConfigTestDeleteForm extends ConfirmFormBase {

  /**
   * The config_test entity to be deleted.
   *
   * @var \Drupal\config_test\Plugin\Core\Entity\ConfigTest.
   */
  protected $configTest;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete %label', array('%label' => $this->configTest->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/structure/config_test';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'config_test_delete_form';
  }

  /**
   * Implements \Drupal\Drupal\Core\Form\ConfirmFormBase::buildForm().
   *
   * @param \Drupal\config_test\ConfigTestInterface $config_test
   *   (optional) The ConfigTestInterface object to delete.
   */
  public function buildForm(array $form, array &$form_state, ConfigTestInterface $config_test = NULL) {
    $this->configTest = $config_test;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configTest->delete();
    drupal_set_message(String::format('%label configuration has been deleted.', array('%label' => $this->configTest->label())));
    $form_state['redirect'] = 'admin/structure/config_test';
  }

}
