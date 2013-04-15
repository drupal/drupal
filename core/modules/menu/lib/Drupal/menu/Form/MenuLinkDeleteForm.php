<?php

/**
 * @file
 * Contains \Drupal\menu\Form\MenuLinkDeleteForm.
 */

namespace Drupal\menu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\menu_link\Plugin\Core\Entity\MenuLink;

/**
 * Defines a confirmation form for deletion of a single menu link.
 */
class MenuLinkDeleteForm extends ConfirmFormBase {

  /**
   * The menu link object to be deleted.
   *
   * @var \Drupal\menu_link\Plugin\Core\Entity\MenuLink
   */
  protected $menuLink;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the custom menu link %item?', array('%item' => $this->menuLink->link_title));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/structure/menu/manage/' . $this->menuLink->menu_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'menu_link_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, MenuLink $menu_link = NULL) {
    $this->menuLink = $menu_link;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    menu_link_delete($this->menuLink->id());
    $t_args = array('%title' => $this->menuLink->link_title);
    drupal_set_message(t('The menu link %title has been deleted.', $t_args));
    watchdog('menu', 'Deleted menu link %title.', $t_args, WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/menu/manage/' . $this->menuLink->menu_name;
  }
}
