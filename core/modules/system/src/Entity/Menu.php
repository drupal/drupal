<?php

namespace Drupal\system\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\system\MenuInterface;

/**
 * Defines the Menu configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "menu",
 *   label = @Translation("Menu"),
 *   label_collection = @Translation("Menus"),
 *   label_singular = @Translation("menu"),
 *   label_plural = @Translation("menus"),
 *   label_count = @PluralTranslation(
 *     singular = "@count menu",
 *     plural = "@count menus",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\system\MenuAccessControlHandler"
 *   },
 *   admin_permission = "administer menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "locked",
 *   }
 * )
 */
class Menu extends ConfigEntityBase implements MenuInterface {

  /**
   * The menu machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the menu entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The menu description.
   *
   * @var string
   */
  protected $description;

  /**
   * The locked status of this menu.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    foreach ($entities as $menu) {
      // Delete all links from the menu.
      $menu_link_manager->deleteLinksInMenu($menu->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();
    \Drupal::cache('menu')->invalidateAll();
    // Invalidate the block cache to update menu-based derivatives.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    \Drupal::cache('menu')->invalidateAll();

    // Invalidate the block cache to update menu-based derivatives.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
  }

}
