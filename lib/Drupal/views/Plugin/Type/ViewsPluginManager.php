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
    $this->factory = new DefaultFactory($this->discovery);
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
    );
  }

  /**
   * Creates a plugin from an id.
   */
  public function createPluginFromId($id) {
    $definition = $this->getDefinition($id);
    if (!empty($definition)) {
      $plugin = $this->createPluginFromDefinition($defintion);
      return $plugin;
    }
  }

  /**
   * Creates a plugin from a definition.
   */
  public function createPluginFromDefinition($definition) {
    $instance = $this->createInstance($definition['id']);
    $instance->is_plugin = TRUE;
    $instance->plugin_type = $this->$type;
    $instance->setDefinition($definition);

    // Let the handler have something like a constructor.
    $instance->construct();

    return $instance;
  }

  /**
   * Creates a handler from a definition.
   */
  public function createHandlerFromDefinition($definition) {
    // @todo This is crazy. Find a way to remove the override functionality.
    $id = !empty($definition['override handler']) ? $definition['override handler'] : $definition['id'];
    try {
      $instance = $this->createInstance($id);
    }
    catch (PluginException $e) {
      $instance = $this->createInstance($definition['id']);
    }

    $instance->is_handler = TRUE;
    $instance->plugin_type = $this->$type;
    $instance->setDefinition($definition);

    // let the handler have something like a constructor.
    $instance->construct();

    return $instance;
  }

}
