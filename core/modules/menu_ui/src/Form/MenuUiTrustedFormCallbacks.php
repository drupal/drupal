<?php

namespace Drupal\menu_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements Trusted Form Callbacks for MenuUi module..
 *
 * @package Drupal\menu_ui\Form
 */
class MenuUiTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #validate callback for menu_ui_form_node_type_form_alter()
   */
  public static function formNodeTypeFormValidate(&$form, FormStateInterface $form_state) {
    $available_menus = array_filter($form_state->getValue('menu_options'));
    // If there is at least one menu allowed, the selected item should be in
    // one of them.
    if (count($available_menus)) {
      $menu_item_id_parts = explode(':', $form_state->getValue('menu_parent'));
      if (!in_array($menu_item_id_parts[0], $available_menus)) {
        $form_state->setErrorByName('menu_parent', t('The selected menu link is not under one of the selected menus.'));
      }
    }
    else {
      $form_state->setValue('menu_parent', '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['formNodeTypeFormValidate'];
  }

}
