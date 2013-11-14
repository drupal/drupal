<?php

/**
 * @file
 * Contains \Drupal\menu\Form\MenuLinkResetForm.
 */

namespace Drupal\menu\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Defines a confirmation form for resetting a single modified menu link.
 */
class MenuLinkResetForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to reset the link %item to its default values?', array('%item' => $this->entity->link_title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'menu.menu_edit',
      'route_parameters' => array(
        'menu' => $this->entity->menu_name,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Any customizations will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $new_menu_link = $this->entity->reset();
    drupal_set_message(t('The menu link was reset to its default settings.'));
    $form_state['redirect_route'] = array(
      'route_name' => 'menu.menu_edit',
      'route_parameters' => array(
        'menu' => $new_menu_link->menu_name,
      ),
    );
  }

}
