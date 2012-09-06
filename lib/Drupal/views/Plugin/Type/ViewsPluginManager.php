<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\ViewsPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class ViewsPluginManager extends PluginManagerBase {

  /**
   * The handler type of this plugin manager, for example filter or field.
   *
   * @var string
   */
  protected $type;

  /**
   * A list of Drupal core modules.
   *
   * @var array
   */
  protected $coreModules = array();

  public function __construct($type) {
    // @todo Remove this hack in http://drupal.org/node/1708404.
    views_init();

    $this->type = $type;

    $this->discovery = new CacheDecorator(new AnnotatedClassDiscovery('views', $this->type), 'views:' . $this->type, 'views');
    $this->factory = new DefaultFactory($this);
    $this->coreModules = views_core_modules();
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // If someone adds an invalid plugin ID, don't provide defaults.
    // views_get_plugin() will then don't createInstance.
    if (empty($definition)) {
      return;
    }

    $module = isset($definition['module']) ? $definition['module'] : 'views';
    // If this module is a core module, use views as the module directory.
    $module_dir = in_array($module, $this->coreModules) ? 'views' : $module;

    // Setup automatic path/file finding for theme registration.
    if ($module_dir == 'views') {
      $theme_path = drupal_get_path('module', $module_dir) . '/theme';
      $theme_file = 'theme.inc';
    }
    else {
      $theme_path = $path = drupal_get_path('module', $module_dir);
      $theme_file = "$module.views.inc";
    }

    $definition += array(
      'module' => $module_dir,
      'theme path' => $theme_path,
      'theme file' => $theme_file,
      'parent' => 'parent',
      'plugin_type' => $this->type,
    );
  }

}
