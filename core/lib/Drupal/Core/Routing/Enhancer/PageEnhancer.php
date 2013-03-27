<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\PageEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances a page route with the appropriate controller.
 */
class PageEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\CoreContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\PageEnhancer object.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * Implements \Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface::enhance()
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_controller']) && !empty($defaults['_content']) && $this->negotiation->getContentType($request) === 'html') {
      $defaults['_controller'] = '\Drupal\Core\HtmlPageController::content';
    }
    return $defaults;
  }

}
