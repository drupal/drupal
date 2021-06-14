<?php

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines the storage class for menu configuration entities.
 */
class MenuStorage extends ConfigEntityStorage {

  /**
   * Menu names have a maximum length of 32.
   *
   * This is based on:
   * - menu_tree table schema definition,
   * - \Drupal\Core\Config\Entity\ConfigEntityStorage::MAX_ID_LENGTH
   * - menu_name base field on the Menu Link content entity.
   *
   * @see \Drupal\Core\Menu\MenuTreeStorage::schemaDefinition()
   * @see \Drupal\menu_link_content\Entity\MenuLinkContent::baseFieldDefinitions()
   */
  const MAX_ID_LENGTH = 32;

}
