<?php

/**
 * @file
 * Contains \Drupal\picture\Form\PictureMappingActionConfirm.
 */

namespace Drupal\picture\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

class PictureMappingDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the picture_mapping %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'picture.mapping_page',
    );
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Picture mapping %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('picture', 'Picture mapping %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/config/media/picturemapping';
  }

}
