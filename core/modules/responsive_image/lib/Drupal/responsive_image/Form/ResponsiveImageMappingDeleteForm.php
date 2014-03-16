<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Form\ResponsiveImageMappingActionConfirm.
 */

namespace Drupal\responsive_image\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

class ResponsiveImageMappingDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the responsive image mapping %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'responsive_image.mapping_page',
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
    drupal_set_message(t('Responsive image mapping %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('responsive_image', 'Responsive image mapping %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route']['route_name'] = 'responsive_image.mapping_page';
  }

}
