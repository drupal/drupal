<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutForm.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the shortcut entity forms.
 */
class ShortcutForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->save();

    if ($entity->isNew()) {
      $message = $this->t('The shortcut %link has been updated.', array('%link' => $entity->getTitle()));
    }
    else {
      $message = $this->t('Added a shortcut for %title.', array('%title' => $entity->getTitle()));
    }
    drupal_set_message($message);

    $form_state->setRedirect(
      'entity.shortcut_set.customize_form',
      array('shortcut_set' => $entity->bundle())
    );
  }

}
