<?php
/**
 * @file
 * Contains \Drupal\config_test\Form\ConfigTestDeleteForm.
 */

namespace Drupal\config_test\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Delete confirmation form for config_test entities.
 */
class ConfigTestDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %label', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/config_test';
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(String::format('%label configuration has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect'] = 'admin/structure/config_test';
  }

}
