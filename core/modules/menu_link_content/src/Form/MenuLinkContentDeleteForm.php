<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Form\MenuLinkContentDeleteForm.
 */

namespace Drupal\menu_link_content\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a delete form for content menu links.
 */
class MenuLinkContentDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the custom menu link %item?', array('%item' => $this->entity->getTitle()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('menu_ui.menu_edit', array('menu' => $this->entity->getMenuName()));
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $storage = $this->entityManager->getStorage('menu_link_content');
    $storage->delete(array($this->entity));
    $t_args = array('%title' => $this->entity->getTitle());
    drupal_set_message($this->t('The menu link %title has been deleted.', $t_args));
    watchdog('menu', 'Deleted menu link %title.', $t_args, WATCHDOG_NOTICE);
    $form_state['redirect_route'] = array(
      'route_name' => '<front>',
    );
  }

}
