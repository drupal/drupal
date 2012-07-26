<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Discovery\ViewsDiscovery.
 */

namespace Drupal\views\Plugins\Discovery;
use Drupal\Core\Plugin\Discovery\HookDiscovery;

/**
 * Discovery interface which supports the hook_views_plugins mechanism.
 */
class ViewsDiscovery extends HookDiscovery {
  /**
   * The plugin type in views which should be discovered, for example query.
   *
   * @var string
   */
  protected $viewsPluginType;

  /**
   * Constructs a Drupal\views\Plugin\Discovery\ViewsDiscovery object.
   *
   * @param string $hook
   *   The Drupal hook that a module can implement in order to interface to
   *   this discovery class.
   * @param string $plugin_type
   *   The plugin type in views which should be discovered, for example query.
   */
  function __construct($hook, $plugin_type) {
    $this->viewsPluginType = $plugin_type;
    parent::__construct($hook);
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    views_include('plugins');
    views_include_handlers();

    $definitions = module_invoke_all($this->hook);
    drupal_alter($this->hook, $definitions);
    return $definitions[$this->viewsPluginType];
  }
}
