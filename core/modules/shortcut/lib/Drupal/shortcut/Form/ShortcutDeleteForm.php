<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\ShortcutDeleteForm.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

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
    return $this->t('Are you sure you want to delete the shortcut %title?', array('%title' => $this->entity->title->value));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'shortcut.set_customize',
      'route_parameters' => array(
        'shortcut_set' => $this->entity->bundle(),
      ),
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
    $form_state['redirect_route'] = array(
      'route_name' => 'shortcut.set_customize',
      'route_parameters' => array(
        'shortcut_set' => $this->entity->bundle(),
      ),
    );
    drupal_set_message($this->t('The shortcut %title has been deleted.', array('%title' => $this->entity->title->value)));
  }

}
