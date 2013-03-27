<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\AjaxEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances an ajax route with the appropriate controller.
 */
class AjaxEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\CoreContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\AjaxEnhancer object.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * Implements \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface::enhance()
   */
  public function enhance(array $defaults, Request $request) {
    // Old-style routes work differently, since they are their own controller.
    if ($request->attributes->get('_legacy') == TRUE) {
      if (empty($defaults['_content']) && $this->negotiation->getContentType($request) == 'drupal_ajax') {
        $defaults['_content'] = $defaults['_controller'];
        $defaults['_controller'] = '\Drupal\Core\AjaxController::content';
      }
    }
    else {
      if (empty($defaults['_controller']) && !empty($defaults['_content']) && $this->negotiation->getContentType($request) === 'drupal_ajax') {
        $defaults['_controller'] = '\Drupal\Core\AjaxController::content';
      }
    }
    return $defaults;
  }

}
