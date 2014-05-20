<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Form\ResponsiveImageMappingActionConfirm.
 */

namespace Drupal\responsive_image\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

class ResponsiveImageMappingDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the responsive image mapping %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('responsive_image.mapping_page');
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Responsive image mapping %label has been deleted.', array('%label' => $this->entity->label())));
    watchdog('responsive_image', 'Responsive image mapping %label has been deleted.', array('%label' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
