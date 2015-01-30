<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\ShortcutDeleteForm.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Builds the shortcut link deletion form.
 */
class ShortcutDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcut_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.shortcut_set.customize_form', array(
      'shortcut_set' => $this->entity->bundle(),
    ));
  }

}
