<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for default HTTP 4xx responses.
 */
class Http4xxController extends ControllerBase {

  /**
   * The default 4xx error content.
   *
   * @return array
   *   A render array containing the message to display for 4xx errors.
   */
  public function on4xx() {
    return [
      '#markup' => $this->t('A client error happened'),
    ];
  }

  /**
   * The default 401 content.
   *
   * @return array
   *   A render array containing the message to display for 401 pages.
   */
  #[Route(
    path: '/system/401',
    name: 'system.401',
    requirements: ['_access' => 'TRUE'],
    defaults: ['_title' => new TranslatableMarkup('Unauthorized')],
  )]
  public function on401() {
    return [
      '#markup' => $this->t('Log in to access this page.'),
    ];
  }

  /**
   * The default 403 content.
   *
   * @return array
   *   A render array containing the message to display for 403 pages.
   */
  public function on403() {
    return [
      '#markup' => $this->t('You are not authorized to access this page.'),
    ];
  }

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    return [
      '#markup' => $this->t('The requested page could not be found.'),
    ];
  }

}
