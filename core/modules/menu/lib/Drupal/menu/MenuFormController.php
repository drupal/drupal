<?php

/**
 * @file
 * Contains Drupal\menu\MenuFormController.
 */

namespace Drupal\menu;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form controller for menu edit forms.
 */
class MenuFormController extends EntityFormController implements EntityControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
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
      '#field_prefix' => $menu->isNew() ? 'menu-' : '',
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

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Menu language'),
      '#languages' => Language::STATE_ALL,
      '#default_value' => $menu->langcode,
    );
    // Unlike the menu langcode, the default language configuration for menu
    // links only works with language module installed.
    if ($this->moduleHandler->moduleExists('language')) {
      $form['default_menu_links_language'] = array(
        '#type' => 'details',
        '#title' => t('Menu links language'),
      );
      $form['default_menu_links_language']['default_language'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'menu_link',
          'bundle' => $menu->id(),
        ),
        '#default_value' => language_get_default_configuration('menu_link', $menu->id()),
      );
    }

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

    return parent::form($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $menu = $this->entity;

    $system_menus = menu_list_system_menus();
    $actions['delete']['#access'] = !$menu->isNew() && !isset($system_menus[$menu->id()]);

    // Add the language configuration submit handler. This is needed because the
    // submit button has custom submit handlers.
    if ($this->moduleHandler->moduleExists('language')) {
      array_unshift($actions['submit']['#submit'],'language_configuration_element_submit');
      array_unshift($actions['submit']['#submit'], array($this, 'languageConfigurationSubmit'));
    }
    // We cannot leverage the regular submit handler definition because we have
    // button-specific ones here. Hence we need to explicitly set it for the
    // submit action, otherwise it would be ignored.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      array_unshift($actions['submit']['#submit'], 'content_translation_language_configuration_element_submit');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    if ($this->entity->isNew()) {
      // The machine name is validated automatically, we only need to add the
      // 'menu-' prefix here.
      $form_state['values']['id'] = 'menu-' . $form_state['values']['id'];
    }
  }

  /**
   * Submit handler to update the bundle for the default language configuration.
   */
  public function languageConfigurationSubmit(array &$form, array &$form_state) {
    // Since the machine name is not known yet, and it can be changed anytime,
    // we have to also update the bundle property for the default language
    // configuration in order to have the correct bundle value.
    $form_state['language']['default_language']['bundle'] = $form_state['values']['id'];
    // Clear cache so new menus (bundles) show on the language settings admin
    // page.
    entity_info_cache_clear();
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
