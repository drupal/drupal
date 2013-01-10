<?php

/**
 * @file
 * Contains Drupal\menu\MenuFormController.
 */

namespace Drupal\menu;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Base form controller for menu edit forms.
 */
class MenuFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $menu) {
    $form = parent::form($form, $form_state, $menu);
    $system_menus = menu_list_system_menus();

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $menu->label(),
      '#required' => TRUE,
      // The title of a system menu cannot be altered.
      '#access' => !isset($system_menus[$menu->id()]),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Menu name'),
      '#default_value' => $menu->id(),
      '#maxlength' => MENU_MAX_MENU_NAME_LENGTH_UI,
      '#description' => t('A unique name to construct the URL for the menu. It must only contain lowercase letters, numbers and hyphens.'),
      '#machine_name' => array(
        'exists' => 'menu_edit_menu_name_exists',
        'source' => array('label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      // A menu's machine name cannot be changed.
      '#disabled' => !$menu->isNew() || isset($system_menus[$menu->id()]),
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $menu->description,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    );
    // Only custom menus may be deleted.
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#access' => !$menu->isNew() && !isset($system_menus[$menu->id()]),
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $menu = $this->getEntity($form_state);

    if ($menu->isNew()) {
      // Add 'menu-' to the menu name to help avoid name-space conflicts.
      $menu->set('id', 'menu-' . $menu->id());
    }

    $status = $menu->save();

    $uri = $menu->uri();
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Menu %label has been updated.', array('%label' => $menu->label())));
      watchdog('menu', 'Menu %label has been updated.', array('%label' => $menu->label()), WATCHDOG_NOTICE, l(t('Edit'), $uri['path'] . '/edit'));
    }
    else {
      drupal_set_message(t('Menu %label has been added.', array('%label' => $menu->label())));
      watchdog('menu', 'Menu %label has been added.', array('%label' => $menu->label()), WATCHDOG_NOTICE, l(t('Edit'), $uri['path'] . '/edit'));
    }

    $form_state['redirect'] = 'admin/structure/menu/manage/' . $menu->id();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $menu = $this->getEntity($form_state);
    $form_state['redirect'] = 'admin/structure/menu/manage/' . $menu->id() . '/delete';
  }

}
