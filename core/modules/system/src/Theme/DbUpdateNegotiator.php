<?php

namespace Drupal\system\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
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
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a DbUpdateNegotiator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface|null $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandlerInterface $theme_handler = NULL) {
    $this->configFactory = $config_factory;
    if ($theme_handler === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $theme_handler argument is deprecated in drupal:9.4.0 and $theme_handler argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3279699', E_USER_DEPRECATED);
      $theme_handler = \Drupal::service('theme_handler');
    }
    $this->themeHandler = $theme_handler;
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
    $custom_theme = Settings::get('maintenance_theme');
    if (!$custom_theme) {
      $custom_theme = $this->themeHandler->themeExists('claro') ? 'claro' : 'seven';
    }

    return $custom_theme;
  }

}
