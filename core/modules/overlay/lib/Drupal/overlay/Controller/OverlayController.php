<?php

/**
 * @file
 * Contains \Drupal\overlay\Controller\OverlayController.
 */

namespace Drupal\overlay\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for overlay routes.
 *
 * @todo keeping the controllerInterface since we should be injecting
 * something to take care of the overlay_render_region() call.
 */
class OverlayController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The userdata service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfGenerator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a OverlayController instance.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The userdata service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_generator
   *   The CSRF token generator.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(UserDataInterface $user_data, CsrfTokenGenerator $csrf_generator, AccountInterface $account) {
    $this->userData = $user_data;
    $this->csrfGenerator = $csrf_generator;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('csrf_token'),
      $container->get('current_user')
    );
  }

  /**
   * Prints the markup obtained by rendering a single region of the page.
   *
   * @todo add a DI for managing the overlay_render_region() call.
   *
   * @param string
   *   The name of the page region to render.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *
   * @see Drupal.overlay.refreshRegions()
   */
  public function regionRender($region) {
    return new Response(overlay_render_region($region));
  }

  /**
   * Dismisses the overlay accessibility message for this user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when a non valid token was specified.
   *
   * @return \Drupal\Core\Controller\ControllerBase
   *   Redirects to the user's edit page.
   */
  public function overlayMessage(Request $request) {
    // @todo Integrate CSRF link token directly into routing system:
    //   http://drupal.org/node/1798296.
    $token = $request->attributes->get('token');
    if (!isset($token) || !$this->csrfGenerator->validate($token, 'overlay')) {
      throw new AccessDeniedHttpException();
    }
    $this->userData->set('overlay', $this->account->id(), 'message_dismissed', 1);
    drupal_set_message($this->t('The message has been dismissed. You can change your overlay settings at any time by visiting your profile page.'));
    // Destination is normally given. Go to the user profile as a fallback.
    return $this->redirect('user_edit', array('user' => $this->account->id()));
  }

}
