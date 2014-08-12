<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\StaticMenuLinkOverridesInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for objects which overrides menu links defined in YAML.
 */
interface StaticMenuLinkOverridesInterface {

  /**
   * Reloads the overrides from config.
   *
   * Forces all overrides to be reloaded from config storage to compare the
   * override value with the value submitted during test form submission.
   */
  public function reload();

  /**
   * Loads any overrides to the definition of a static (YAML-defined) link.
   *
   * @param string $id
   *   A menu link plugin ID.
   *
   * @return array|NULL
   *   An override with following supported keys:
   *     - parent
   *     - weight
   *     - menu_name
   *     - expanded
   *     - enabled
   *   or NULL if there is no override for the given ID.
   */
  public function loadOverride($id);

  /**
   * Deletes any overrides to the definition of a static (YAML-defined) link.
   *
   * @param string $id
   *   A menu link plugin ID.
   */
  public function deleteOverride($id);

  /**
   * Deletes multiple overrides to definitions of static (YAML-defined) links.
   *
   * @param array $ids
   *   Array of menu link plugin IDs.
   */
  public function deleteMultipleOverrides(array $ids);

  /**
   * Loads overrides to multiple definitions of a static (YAML-defined) link.
   *
   * @param array $ids
   *   Array of menu link plugin IDs.
   *
   * @return array
   *   One or override keys by plugin ID.
   *
   * @see \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  public function loadMultipleOverrides(array $ids);

  /**
   * Saves the override.
   *
   * @param string $id
   *   A menu link plugin ID.
   * @param array $definition
   *   The definition values to override. Supported keys:
   *   - menu_name
   *   - parent
   *   - weight
   *   - expanded
   *   - enabled
   *
   * @return array
   *   A list of properties which got saved.
   */
  public function saveOverride($id, array $definition);

}
