<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\StaticMenuLinks.
 */

namespace Drupal\menu_link;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a service which finds and alters default menu links in yml files.
 */
class StaticMenuLinks {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new StaticMenuLinks.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets the menu links defined in YAML files.
   *
   * @return array
   *   An array of default menu links.
   */
  public function getLinks() {
    $discovery = $this->getDiscovery();
    foreach ($discovery->findAll() as $module => $menu_links) {
      foreach ($menu_links as $machine_name => $menu_link) {
        $all_links[$machine_name] = $menu_link;
        $all_links[$machine_name]['machine_name'] = $machine_name;
        $all_links[$machine_name]['module'] = $module;
      }
    }

    $this->moduleHandler->alter('menu_link_defaults', $all_links);
    foreach ($all_links as $machine_name => $menu_link) {
      // Set the machine_name to the menu links added dynamically.
      if (!isset($menu_link['machine_name'])) {
        $all_links[$machine_name]['machine_name'] = $machine_name;
      }
      // Change the key to match the DB column for now.
      $all_links[$machine_name]['link_title'] = $all_links[$machine_name]['title'];
      unset($all_links[$machine_name]['title']);
    }

    return $all_links;
  }

  /**
   * Creates a YAML discovery for menu links.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   An YAML discovery instance.
   */
  protected function getDiscovery() {
    return new YamlDiscovery('menu_links', $this->moduleHandler->getModuleDirectories());
  }

}

