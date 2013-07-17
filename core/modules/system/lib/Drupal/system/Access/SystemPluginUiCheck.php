<?php

/**
 * @file
 * Contains \Drupal\system\Access\SystemPluginUiCheck.
 */

namespace Drupal\system\Access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for system routes.
 */
class SystemPluginUiCheck implements AccessCheckInterface {

  /**
   * The plugin UI manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginUiManager;

  /**
   * Constructs a SystemController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_ui_manager
   *   The plugin UI manager.
   */
  public function __construct(PluginManagerInterface $plugin_ui_manager) {
    $this->pluginUiManager = $plugin_ui_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_access_system_plugin_ui', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($request->attributes->get('plugin_id')) {
      // Checks access for a given plugin using the plugin's access() method.
      $plugin_ui = $this->pluginUiManager->createInstance($request->attributes->get('plugin_id'), array());
      return $plugin_ui->access(NULL) ? static::ALLOW : static::DENY;
    }
  }

}
