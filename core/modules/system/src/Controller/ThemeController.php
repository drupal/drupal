<?php

namespace Drupal\system\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for theme handling.
 */
class ThemeController extends ControllerBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * Constructs a new ThemeController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   *   The theme installer.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, ThemeInstallerInterface $theme_installer) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->themeInstaller = $theme_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('theme_installer')
    );
  }

  /**
   * Uninstalls a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function uninstall(Request $request) {
    $theme = $request->query->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not uninstall the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          $this->messenger()->addError($this->t('%theme is the default theme and cannot be uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
        }
        else {
          $this->themeInstaller->uninstall([$theme]);
          $this->messenger()->addStatus($this->t('The %theme theme has been uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Installs a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function install(Request $request) {
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      try {
        if ($this->themeInstaller->install([$theme])) {
          $themes = $this->themeHandler->listInfo();
          $this->messenger()->addStatus($this->t('The %theme theme has been installed.', ['%theme' => $themes[$theme]->info['name']]));
        }
        else {
          $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
        }
      }
      catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        $this->messenger()->addError(
          $this->formatPlural(
            count($config_objects),
            'Unable to install @extension, %config_names already exists in active configuration.',
            'Unable to install @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $theme,
            ])
        );
      }
      catch (UnmetDependenciesException $e) {
        $this->messenger()->addError($e->getTranslatedMessage($this->getStringTranslation(), $theme));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Set the default theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme is set in the request.
   */
  public function setDefaultTheme(Request $request) {
    $config = $this->configFactory->getEditable('system.theme');
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      // Or try to install the theme.
      if (isset($themes[$theme]) || $this->themeInstaller->install([$theme])) {
        $themes = $this->themeHandler->listInfo();

        // Set the default theme.
        $config->set('default', $theme)->save();

        // The status message depends on whether an admin theme is currently in
        // use: a value of 0 means the admin theme is set to be the default
        // theme.
        $admin_theme = $config->get('admin');
        if ($admin_theme != 0 && $admin_theme != $theme) {
          $this->messenger()
            ->addStatus($this->t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', [
              '%admin_theme' => $themes[$admin_theme]->info['name'],
              '%selected_theme' => $themes[$theme]->info['name'],
            ]));
        }
        else {
          $this->messenger()->addStatus($this->t('%theme is now the default theme.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }

      return $this->redirect('system.themes_page');

    }
    throw new AccessDeniedHttpException();
  }

}
