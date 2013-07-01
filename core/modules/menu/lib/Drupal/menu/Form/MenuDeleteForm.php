<?php

/**
 * @file
 * Contains \Drupal\menu\Form\MenuDeleteForm.
 */

namespace Drupal\menu\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for deletion of a custom menu.
 */
class MenuDeleteForm extends EntityConfirmFormBase implements EntityControllerInterface {

  /**
   * The menu link storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new MenuDeleteForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The menu link storage controller.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityStorageControllerInterface $storage_controller, Connection $connection) {
    parent::__construct($module_handler);
    $this->storageController = $storage_controller;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.entity')->getStorageController('menu_link'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the custom menu %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/menu/manage/' . $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $caption = '';
    $num_links = $this->storageController->countMenuLinks($this->entity->id());
    if ($num_links) {
      $caption .= '<p>' . format_plural($num_links, '<strong>Warning:</strong> There is currently 1 menu link in %title. It will be deleted (system-defined items will be reset).', '<strong>Warning:</strong> There are currently @count menu links in %title. They will be deleted (system-defined links will be reset).', array('%title' => $this->entity->label())) . '</p>';
    }
    $caption .= '<p>' . t('This action cannot be undone.') . '</p>';
    return $caption;
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
  public function submit(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/structure/menu';

    // System-defined menus may not be deleted - only menus defined by this module.
    $system_menus = menu_list_system_menus();
    if (isset($system_menus[$this->entity->id()])) {
      return;
    }

    // Reset all the menu links defined by the system via hook_menu().
    // @todo Convert this to an EFQ once we figure out 'ORDER BY m.number_parts'.
    $result = $this->connection->query("SELECT mlid FROM {menu_links} ml INNER JOIN {menu_router} m ON ml.router_path = m.path WHERE ml.menu_name = :menu AND ml.module = 'system' ORDER BY m.number_parts ASC", array(':menu' => $this->entity->id()), array('fetch' => \PDO::FETCH_ASSOC))->fetchCol();
    $menu_links = $this->storageController->loadMultiple($result);
    foreach ($menu_links as $link) {
      $link->reset();
    }

    // Delete all links to the overview page for this menu.
    $menu_links = $this->storageController->loadByProperties(array('link_path' => 'admin/structure/menu/manage/' . $this->entity->id()));
    menu_link_delete_multiple(array_keys($menu_links));

    // Delete the custom menu and all its menu links.
    $this->entity->delete();

    $t_args = array('%title' => $this->entity->label());
    drupal_set_message(t('The custom menu %title has been deleted.', $t_args));
    watchdog('menu', 'Deleted custom menu %title and all its menu links.', $t_args, WATCHDOG_NOTICE);
  }
}
