<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\FormEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances a form route with the appropriate controller.
 */
class FormEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\CoreContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new \Drupal\Core\Routing\Enhancer\FormEnhancer object.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The Content Negotiation service.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * {@inhertdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_controller']) && !empty($defaults['_form']) && $this->negotiation->getContentType($request) === 'html') {
      $defaults['_controller'] = '\Drupal\Core\Controller\HtmlFormController::content';
    }
    return $defaults;
  }

}
