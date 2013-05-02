<?php

/**
 * @file
 * Contains \Drupal\menu\Form\MenuDeleteMenuForm.
 */

namespace Drupal\menu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\system\Plugin\Core\Entity\Menu;

/**
 * Defines a confirmation form for deletion of a custom menu.
 */
class MenuDeleteMenuForm extends ConfirmFormBase {

  /**
   * The menu object to be deleted.
   *
   * @var \Drupal\system\Plugin\Core\Entity\Menu
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure you want to delete the custom menu %title?', array('%title' => $this->menu->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/structure/menu/manage/' . $this->menu->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription() {
    $caption = '';
    $num_links = \Drupal::entityManager()
      ->getStorageController('menu_link')->countMenuLinks($this->menu->id());
    if ($num_links) {
      $caption .= '<p>' . format_plural($num_links, '<strong>Warning:</strong> There is currently 1 menu link in %title. It will be deleted (system-defined items will be reset).', '<strong>Warning:</strong> There are currently @count menu links in %title. They will be deleted (system-defined links will be reset).', array('%title' => $this->menu->label())) . '</p>';
    }
    $caption .= '<p>' . t('This action cannot be undone.') . '</p>';
    return $caption;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'menu_delete_menu_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Menu $menu = NULL) {
    $this->menu = $menu;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect'] = 'admin/structure/menu';

    // System-defined menus may not be deleted - only menus defined by this module.
    $system_menus = menu_list_system_menus();
    if (isset($system_menus[$this->menu->id()])) {
      return;
    }

    // Reset all the menu links defined by the system via hook_menu().
    // @todo Convert this to an EFQ once we figure out 'ORDER BY m.number_parts'.
    $result = db_query("SELECT mlid FROM {menu_links} ml INNER JOIN {menu_router} m ON ml.router_path = m.path WHERE ml.menu_name = :menu AND ml.module = 'system' ORDER BY m.number_parts ASC", array(':menu' => $this->menu->id()), array('fetch' => \PDO::FETCH_ASSOC))->fetchCol();
    $menu_links = menu_link_load_multiple($result);
    foreach ($menu_links as $link) {
      $link->reset();
    }

    // Delete all links to the overview page for this menu.
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => 'admin/structure/menu/manage/' . $this->menu->id()));
    menu_link_delete_multiple(array_keys($menu_links));

    // Delete the custom menu and all its menu links.
    $this->menu->delete();

    $t_args = array('%title' => $this->menu->label());
    drupal_set_message(t('The custom menu %title has been deleted.', $t_args));
    watchdog('menu', 'Deleted custom menu %title and all its menu links.', $t_args, WATCHDOG_NOTICE);
  }
}
