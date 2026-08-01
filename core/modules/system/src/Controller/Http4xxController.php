<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
  #[Route(
    path: '/system/4xx',
    name: 'system.4xx',
    title: new TranslatableMarkup('Client error'),
    requirements: ['_access' => 'TRUE'],
  )]
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
    title: new TranslatableMarkup('Unauthorized'),
    requirements: ['_access' => 'TRUE'],
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
  #[Route(
    path: '/system/403',
    name: 'system.403',
    title: new TranslatableMarkup('Access denied'),
    requirements: ['_access' => 'TRUE'],
  )]
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
  #[Route(
    path: '/system/404',
    name: 'system.404',
    title: new TranslatableMarkup('Page not found'),
    requirements: ['_access' => 'TRUE'],
  )]
  public function on404() {
    return [
      '#markup' => $this->t('The requested page could not be found.'),
    ];
  }

}
