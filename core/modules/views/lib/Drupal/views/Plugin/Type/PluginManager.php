<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\PluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class PluginManager extends PluginManagerBase {

  /**
   * Constructs a PluginManager object.
   */
  public function __construct($type) {
    $this->discovery = new CacheDecorator(new AlterDecorator(new AnnotatedClassDiscovery('views', $type), 'views_plugins_' . $type), 'views:' . $type, 'views_info');
    $this->factory = new DefaultFactory($this);
    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'module' => 'views',
    );
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Setup automatic path/file finding for theme registration.
    if ($definition['module'] == 'views' || isset($definition['theme'])) {
      $definition += array(
        'theme path' => drupal_get_path('module', 'views') . '/theme',
        'theme file' => 'theme.inc',
      );
    }
  }

}
