<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserController.
 */

namespace Drupal\user\Controller;

use Drupal\user\Form\UserLoginForm;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for user routes.
 */
class UserController extends ContainerAware {

  /**
   * Returns the user page.
   *
   * Displays user profile if user is logged in, or login form for anonymous
   * users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Returns either a redirect to the user page or the render
   *   array of the login form.
   */
  public function userPage(Request $request) {
    global $user;
    if ($user->id()) {
      $response = new RedirectResponse(url('user/' . $user->id(), array('absolute' => TRUE)));
    }
    else {
      $response = drupal_get_form(UserLoginForm::create($this->container), $request);
    }
    return $response;
  }

  /**
   * Logs the current user out.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout(Request $request) {
    user_logout();
    return new RedirectResponse(url('<front>', array('absolute' => TRUE)));
  }

}
