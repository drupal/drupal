<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\LinkDelete.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\menu_link\Plugin\Core\Entity\MenuLink;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the shortcut link deletion form.
 */
class LinkDelete extends ConfirmFormBase {

  /**
   * The menu link to delete.
   *
   * @var \Drupal\menu_link\Plugin\Core\Entity\MenuLink
   */
  protected $menuLink;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'shortcut_link_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the shortcut %title?', array('%title' => $this->menuLink->link_title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/user-interface/shortcut/manage/' . $this->menuLink->menu_name;
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
  public function buildForm(array $form, array &$form_state, MenuLink $menu_link = NULL, Request $request = NULL) {
    $this->menuLink = $menu_link;

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    menu_link_delete($this->menuLink->mlid);
    $set_name = str_replace('shortcut-', '' , $this->menuLink->menu_name);
    $form_state['redirect'] = 'admin/config/user-interface/shortcut/manage/' . $set_name;
    drupal_set_message(t('The shortcut %title has been deleted.', array('%title' => $this->menuLink->link_title)));
  }

}
