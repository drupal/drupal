<?php

/**
 * @file
 * Contains \Drupal\picture\Form\PictureMappingActionConfirm.
 */

namespace Drupal\picture\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityInterface;

class PictureMappingActionConfirmForm extends ConfirmFormBase {

  /**
   * The picture mapping object to be deleted.
   *
   * @var type \Drupal\Core\Entity\EntityInterface
   */
  protected $pictureMapping;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the picture_mapping %title?', array('%title' => $this->pictureMapping->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/config/media/picturemapping';
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
  public function getFormID() {
    return 'picture_mapping_action_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, EntityInterface $picture_mapping = NULL) {
    $this->pictureMapping = $picture_mapping;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->pictureMapping->delete();
    drupal_set_message(t('Picture mapping %label has been deleted.', array('%label' => $this->pictureMapping->label())));
    watchdog('picture', 'Picture mapping %label has been deleted.', array('%label' => $this->pictureMapping->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/config/media/picturemapping';
  }
}
