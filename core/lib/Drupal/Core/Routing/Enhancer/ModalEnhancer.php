<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\ModalEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

/**
 * Enhances a route to use the DialogController for matching requests.
 */
class ModalEnhancer extends DialogEnhancer {

  /**
   * Content type this enhancer targets.
   *
   * @var string
   */
  protected $targetContentType = 'drupal_modal';

  /**
   * Controller to route matching requests to.
   *
   * @var string
   */
  protected $controller = '\Drupal\Core\Ajax\DialogController::modal';

}
