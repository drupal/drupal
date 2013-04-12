<?php

/**
 * @file
 * Contains \Drupal\menu\Form\MenuLinkResetForm.
 */

namespace Drupal\menu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\menu_link\Plugin\Core\Entity\MenuLink;

/**
 * Defines a confirmation form for resetting a single modified menu link.
 */
class MenuLinkResetForm extends ConfirmFormBase {

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
    return t('Are you sure you want to reset the link %item to its default values?', array('%item' => $this->menuLink->link_title));
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
  protected function getDescription() {
    return t('Any customizations will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'menu_link_reset_form';
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
    $new_menu_link = $this->menuLink->reset();
    drupal_set_message(t('The menu link was reset to its default settings.'));
    $form_state['redirect'] = 'admin/structure/menu/manage/' . $new_menu_link->menu_name;
  }
}
