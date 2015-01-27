<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\ShortcutDeleteForm.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the shortcut link deletion form.
 */
class ShortcutDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcut_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the shortcut %title?', array('%title' => $this->entity->getTitle()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.shortcut_set.customize_form', array(
      'shortcut_set' => $this->entity->bundle(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $form_state->setRedirectUrl($this->getCancelUrl());
    drupal_set_message($this->t('The shortcut %title has been deleted.', array('%title' => $this->entity->title->value)));
  }

}
