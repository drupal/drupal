<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\DialogEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances a route to use the DialogController for matching requests.
 */
class DialogEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\CoreContentNegotiation
   */
  protected $negotiation;

  /**
   * Content type this enhancer targets.
   *
   * @var string
   */
  protected $targetContentType = 'drupal_dialog';

  /**
   * Controller to route matching requests to.
   *
   * @var string
   */
  protected $controller = '\Drupal\Core\Ajax\DialogController::dialog';

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\AjaxEnhancer object.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The Content Negotiation service.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_controller']) && !empty($defaults['_content']) && $this->negotiation->getContentType($request) == $this->targetContentType) {
      $defaults['_controller'] = $this->controller;
    }
    return $defaults;
  }
}
