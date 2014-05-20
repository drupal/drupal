<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Form\MenuLinkDeleteForm.
 */

namespace Drupal\menu_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deletion of a single menu link.
 */
class MenuLinkDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the custom menu link %item?', array('%item' => $this->entity->link_title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('menu_ui.menu_edit', array(
      'menu' => $this->entity->menu_name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    menu_link_delete($this->entity->id());
    $t_args = array('%title' => $this->entity->link_title);
    drupal_set_message(t('The menu link %title has been deleted.', $t_args));
    watchdog('menu', 'Deleted menu link %title.', $t_args, WATCHDOG_NOTICE);
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
