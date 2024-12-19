<?php

declare(strict_types=1);

namespace Drupal\nightwatch_theme_install_utility\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an easier way for Nightwatch tests to install themes.
 */
class ThemeInstallController extends ControllerBase {

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * Constructs a new ThemeInstallController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   *   The theme installer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeInstallerInterface $theme_installer) {
    $this->configFactory = $config_factory;
    $this->themeInstaller = $theme_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_installer')
    );
  }

  /**
   * Install a theme as default.
   *
   * @param string $theme
   *   The theme to install as the default theme.
   *
   * @return array
   *   A render array confirming installation.
   */
  public function installDefault(string $theme) {
    return $this->installTheme($theme, 'default');
  }

  /**
   * Install a theme as the admin theme.
   *
   * @param string $theme
   *   The theme to install as the admin theme.
   *
   * @return array
   *   A render array confirming installation.
   */
  public function installAdmin($theme) {
    return $this->installTheme($theme, 'admin');
  }

  /**
   * Installs a theme.
   *
   * @param string $theme
   *   The theme to install.
   * @param string $default_or_admin
   *   Which type of theme to install, can be `default` or `admin`.
   *
   * @return array
   *   A render array confirming installation.
   */
  private function installTheme($theme, $default_or_admin): array {
    assert(in_array($default_or_admin, ['default', 'admin']), 'The $default_or_admin parameter must be `default` or `admin`');
    $config = $this->configFactory->getEditable('system.theme');
    $this->themeInstaller->install([$theme]);
    $config->set($default_or_admin, $theme)->save();
    return [
      '#type' => 'container',
      '#attributes' => ['id' => 'theme-installed'],
      '#markup' => "Installed $theme as $default_or_admin theme",
    ];
  }

}
