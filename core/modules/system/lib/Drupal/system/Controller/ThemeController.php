<?php

/**
 * @file
 * Contains \Drupal\system\Controller\ThemeController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for theme handling.
 */
class ThemeController extends ControllerBase {

  /**
   * Disables a theme.
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
  public function disable(Request $request) {
    $theme = $request->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = list_themes();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not disable the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          drupal_set_message(t('%theme is the default theme and cannot be disabled.', array('%theme' => $themes[$theme]->info['name'])), 'error');
        }
        else {
          theme_disable(array($theme));
          drupal_set_message(t('The %theme theme has been disabled.', array('%theme' => $themes[$theme]->info['name'])));
        }
      }
      else {
        drupal_set_message(t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
      }

      return new RedirectResponse(url('admin/appearance', array('absolute' => TRUE)));
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Enables a theme.
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
  public function enable(Request $request) {
    $theme = $request->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = list_themes(TRUE);

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        theme_enable(array($theme));
        drupal_set_message(t('The %theme theme has been enabled.', array('%theme' => $themes[$theme]->info['name'])));
      }
      else {
        drupal_set_message(t('The %theme theme was not found.', array('%theme' => $theme)), 'error');
      }

      return new RedirectResponse(url('admin/appearance', array('absolute' => TRUE)));
    }

    throw new AccessDeniedHttpException();
  }

}
