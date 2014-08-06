<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleFlushForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for image style flush.
 */
class ImageStyleFlushForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to apply the updated %name image effect to all images?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This operation does not change the original images but the copies created for this style will be recreated.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Flush');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('image.style_list');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->flush();
    drupal_set_message($this->t('The image style %name has been flushed.', array('%name' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
