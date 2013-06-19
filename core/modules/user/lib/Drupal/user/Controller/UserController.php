<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserController.
 */

namespace Drupal\user\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerInterface;

/**
 * Controller routines for user routes.
 */
class UserController implements ControllerInterface {

  /**
   * Constructs an UserController object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
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
