<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\ThemeLocalTask.
 */

namespace Drupal\system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic tabs based on active themes.
 */
class ThemeLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new ThemeLocalTask instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
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
    foreach ($this->themeHandler->listInfo() as $theme_name => $theme) {
      if ($this->themeHandler->hasUi($theme_name)) {
        $this->derivatives[$theme_name] = $base_plugin_definition;
        $this->derivatives[$theme_name]['title'] = $theme->info['name'];
        $this->derivatives[$theme_name]['route_parameters'] = array('theme' => $theme_name);
      }
    }
    return $this->derivatives;
  }

}
