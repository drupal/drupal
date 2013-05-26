<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserController.
 */

namespace Drupal\user\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Controller routines for user routes.
 */
class UserController implements ControllerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an UserController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
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
    global $user;

    watchdog('user', 'Session closed for %name.', array('%name' => $user->name));
    $this->moduleHandler->invokeAll('user_logout', array($user));
    // Destroy the current session, and reset $user to the anonymous user.
    session_destroy();
    // @todo Remove the destination check once drupal.org/node/1668866 is in.
    $url = $request->query->get('destination') ?: '<front>';
    return new RedirectResponse(url($url, array('absolute' => TRUE)));
  }

}
