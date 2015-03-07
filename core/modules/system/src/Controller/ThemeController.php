<?php

/**
 * @file
 * Contains \Drupal\system\Controller\ThemeController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteBuilderIndicatorInterface;
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
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderIndicatorInterface
   */
  protected $routeBuilderIndicator;

  /**
   * Constructs a new ThemeController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Routing\RouteBuilderIndicatorInterface $route_builder_indicator
   *   The route builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, RouteBuilderIndicatorInterface $route_builder_indicator, ConfigFactoryInterface $config_factory) {
    $this->themeHandler = $theme_handler;
    $this->routeBuilderIndicator = $route_builder_indicator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('router.builder_indicator'),
      $container->get('config.factory')
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
    $theme = $request->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not uninstall the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          drupal_set_message($this->t('%theme is the default theme and cannot be uninstalled.', array('%theme' => $themes[$theme]->info['name'])), 'error');
        }
        else {
          $this->themeHandler->uninstall(array($theme));
          drupal_set_message($this->t('The %theme theme has been uninstalled.', array('%theme' => $themes[$theme]->info['name'])));
        }
      }
      else {
        drupal_set_message($this->t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
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
    $theme = $request->get('theme');

    if (isset($theme)) {
      try {
        if ($this->themeHandler->install(array($theme))) {
          $themes = $this->themeHandler->listInfo();
          drupal_set_message($this->t('The %theme theme has been installed.', array('%theme' => $themes[$theme]->info['name'])));
        }
        else {
          drupal_set_message($this->t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
        }
      }
      catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        drupal_set_message(
          $this->formatPlural(
            count($config_objects),
            'Unable to install @extension, %config_names already exists in active configuration.',
            'Unable to install @extension, %config_names already exist in active configuration.',
            array(
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $theme,
            )),
          'error'
        );
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
      if (isset($themes[$theme]) || $this->themeHandler->install(array($theme))) {
        $themes = $this->themeHandler->listInfo();

        // Set the default theme.
        $config->set('default', $theme)->save();

        $this->routeBuilderIndicator->setRebuildNeeded();

        // The status message depends on whether an admin theme is currently in
        // use: a value of 0 means the admin theme is set to be the default
        // theme.
        $admin_theme = $config->get('admin');
        if ($admin_theme != 0 && $admin_theme != $theme) {
          drupal_set_message($this->t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', array(
            '%admin_theme' => $themes[$admin_theme]->info['name'],
            '%selected_theme' => $themes[$theme]->info['name'],
          )));
        }
        else {
          drupal_set_message($this->t('%theme is now the default theme.', array('%theme' => $themes[$theme]->info['name'])));
        }
      }
      else {
        drupal_set_message($this->t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
      }

      return $this->redirect('system.themes_page');

    }
    throw new AccessDeniedHttpException();
  }

}
