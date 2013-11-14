<?php

/**
 * @file
 * Contains \Drupal\block\Form\BlockDeleteForm.
 */

namespace Drupal\block\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 */
class BlockDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'block.admin_display',
    );
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
    drupal_set_message($this->t('The block %name has been removed.', array('%name' => $this->entity->label())));
    $form_state['redirect_route']['route_name'] = 'block.admin_display';
  }

}
