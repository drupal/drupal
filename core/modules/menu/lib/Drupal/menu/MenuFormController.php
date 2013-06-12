<?php

/**
 * @file
 * Contains Drupal\menu\MenuFormController.
 */

namespace Drupal\menu;

use Drupal\Core\Entity\EntityFormController;

/**
 * Base form controller for menu edit forms.
 */
class MenuFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $menu = $this->entity;
    $system_menus = menu_list_system_menus();
    $form_state['menu'] = &$menu;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $menu->label(),
      '#required' => TRUE,
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
      '#type' => 'textfield',
      '#title' => t('Administrative summary'),
      '#maxlength' => 512,
      '#default_value' => $menu->description,
    );

    // Add menu links administration form for existing menus.
    if (!$menu->isNew() || isset($system_menus[$menu->id()])) {
      // Form API supports constructing and validating self-contained sections
      // within forms, but does not allow to handle the form section's submission
      // equally separated yet. Therefore, we use a $form_state key to point to
      // the parents of the form section.
      // @see menu_overview_form_submit()
      $form_state['menu_overview_form_parents'] = array('links');
      $form['links'] = array();
      $form['links'] = menu_overview_form($form['links'], $form_state);
    }

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $menu = $this->entity;

    $system_menus = menu_list_system_menus();
    $actions['delete']['#access'] = !$menu->isNew() && !isset($system_menus[$menu->id()]);

    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $menu = $this->entity;
    $system_menus = menu_list_system_menus();

    if (!$menu->isNew() || isset($system_menus[$menu->id()])) {
      menu_overview_form_submit($form, $form_state);
    }

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
    $menu = $this->entity;
    $form_state['redirect'] = 'admin/structure/menu/manage/' . $menu->id() . '/delete';
  }

}
