<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserController.
 */

namespace Drupal\user\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for user routes.
 */
class UserController extends ControllerBase {

  /**
   * Returns the user page.
   *
   * Displays user profile if user is logged in, or login form for anonymous
   * users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Returns either a redirect to the user page or the render
   *   array of the login form.
   */
  public function userPage(Request $request) {
    $user = $this->currentUser();
    if ($user->id()) {
      $response = $this->redirect('user.view', array('user' => $user->id()));
    }
    else {
      $form_builder = $this->formBuilder();
      $response = $form_builder->getForm('Drupal\user\Form\UserLoginForm');
    }
    return $response;
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return string
   *   The user account name.
   */
  public function userTitle(UserInterface $user = NULL) {
    return $user ? Xss::filter($user->getUsername()) : '';
  }

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {
    user_logout();
    return $this->redirect('<front>');
  }

  /**
   * @todo Remove user_cancel_confirm().
   */
  public function confirmCancel(UserInterface $user, $timestamp = 0, $hashed_pass = '') {
    module_load_include('pages.inc', 'user');
    return user_cancel_confirm($user, $timestamp, $hashed_pass);
  }

}
