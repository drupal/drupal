<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\ThemeLocalTask.
 */

namespace Drupal\block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic tabs based on active themes.
 */
class ThemeLocalTask extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * Stores the theme settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new ThemeLocalTask.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config = $config_factory->get('system.theme');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $default_theme = $this->config->get('default');

    foreach (list_themes() as $theme_name => $theme) {
      if ($theme->status) {
        $this->derivatives[$theme_name] = $base_plugin_definition;
        $this->derivatives[$theme_name]['title'] = $theme->info['name'];
        $this->derivatives[$theme_name]['route_parameters'] = array('theme' => $theme_name);
      }
      // Default task!
      if ($default_theme == $theme_name) {
        $this->derivatives[$theme_name]['route_name'] = 'block.admin_display';
        // Emulate default logic because without the base plugin id we can't set the
        // change the tab_root_id.
        $this->derivatives[$theme_name]['weight'] = -10;

        unset($this->derivatives[$theme_name]['route_parameters']);
      }
    }
    return $this->derivatives;
  }

}
