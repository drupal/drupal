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
    // A request can have the 'ajax' content type when the controller supports
    // basically both simple HTML and Ajax routes by returning a render array.
    // In those cases we want to convert it to a proper ajax response as well.
    if (empty($defaults['_content']) && $defaults['_controller'] != 'controller.ajax:content' && in_array($this->negotiation->getContentType($request), array('drupal_ajax', 'ajax', 'iframeupload'))) {
      $defaults['_content'] = isset($defaults['_controller']) ? $defaults['_controller'] : NULL;
      $defaults['_controller'] = 'controller.ajax:content';
    }
    return $defaults;
  }
}
