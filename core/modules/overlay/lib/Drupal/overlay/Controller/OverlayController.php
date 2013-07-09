<?php

/**
 * @file
 * Contains \Drupal\overlay\Controller\OverlayController.
 */

namespace Drupal\overlay\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for overlay routes.
 */
class OverlayController {

  /**
   * Dismisses the overlay accessibility message for this user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when a non valid token was specified.
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the user's edit page.
   *
   */
  public function overlayMessage(Request $request) {
    $account = $request->attributes->get('account');

    // @todo Integrate CSRF link token directly into routing system: http://drupal.org/node/1798296.
    $token = $request->attributes->get('token');
    if (!isset($token) || !drupal_valid_token($token, 'overlay')) {
      throw new AccessDeniedHttpException();
    }
    $request->attributes->get('user.data')->set('overlay', $account->id(), 'message_dismissed', 1);
    drupal_set_message(t('The message has been dismissed. You can change your overlay settings at any time by visiting your profile page.'));
    // Destination is normally given. Go to the user profile as a fallback.
    return new RedirectResponse(url('user/' . $account->id() . '/edit', array('absolute' => TRUE)));
  }

}
