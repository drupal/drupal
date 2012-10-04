<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\WizardManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class WizardManager extends PluginManagerBase {

  /**
   * A list of Drupal core modules.
   *
   * @var array
   */
  protected $coreModules = array();

  /**
   * Constructs a WizardManager object.
   */
  public function __construct() {
    // @todo Remove this hack in http://drupal.org/node/1708404.
    views_init();

    $this->discovery = new CacheDecorator(new AlterDecorator(new AnnotatedClassDiscovery('views', 'wizard'), 'views_plugins_wizard'), 'views:wizard', 'views_info');
    $this->factory = new DefaultFactory($this);

    $this->coreModules = views_core_modules();
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    $module = isset($definition['module']) ? $definition['module'] : 'views';
    $module_dir = in_array($module, $this->coreModules) ? 'views' : $module;

    $definition += array(
      'module' => $module_dir,
    );
  }

}
