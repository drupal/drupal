<?php

namespace Drupal\block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic tabs based on active themes.
 */
class ThemeLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a new ThemeLocalTask.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $default_theme = $this->themeHandler->getDefault();

    foreach ($this->themeHandler->listInfo() as $theme_name => $theme) {
      if ($this->themeHandler->hasUi($theme_name)) {
        $this->derivatives[$theme_name] = $base_plugin_definition;
        $this->derivatives[$theme_name]['title'] = $theme->info['name'];
        $this->derivatives[$theme_name]['route_parameters'] = ['theme' => $theme_name];
      }
      // Default task!
      if ($default_theme == $theme_name) {
        $this->derivatives[$theme_name]['route_name'] = $base_plugin_definition['parent_id'];
        // Emulate default logic because without the base plugin id we can't
        // change the base_route.
        $this->derivatives[$theme_name]['weight'] = -10;

        unset($this->derivatives[$theme_name]['route_parameters']);
      }
    }
    return $this->derivatives;
  }

}
