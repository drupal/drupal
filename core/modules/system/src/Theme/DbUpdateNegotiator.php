<?php

/**
 * @file
 * Contains \Drupal\system\Theme\DbUpdateNegotiator.
 */

namespace Drupal\system\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the active theme for the database update pages.
 */
class DbUpdateNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DbUpdateNegotiator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'system.db_update';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $custom_theme = Settings::get('maintenance_theme', 'seven');
    if (!$custom_theme) {
      $config = $this->configFactory->get('system.theme');
      $custom_theme = $config->get('default');
    }

    return $custom_theme;
  }

}
