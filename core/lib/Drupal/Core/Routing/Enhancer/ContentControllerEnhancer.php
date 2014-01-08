<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\ContentControllerEnhancer.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances a route to select a controller based on the mime type of the request.
 */
class ContentControllerEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * Associative array of supported mime types and their appropriate controller.
   *
   * @var array
   */
  protected $types = array(
    'drupal_dialog' => 'controller.dialog:dialog',
    'drupal_modal' => 'controller.dialog:modal',
    'html' => 'controller.page:content',
    'drupal_ajax' => 'controller.ajax:content',
  );

  /**
   * Constructs a new ContentControllerEnhancer object.
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
    // If no controller is set and either _content is set or the request is
    // for a dialog or modal, then enhance.
    if (empty($defaults['_controller']) && ($type = $this->negotiation->getContentType($request))) {
      if (isset($this->types[$type])) {
        $defaults['_controller'] = $this->types[$type];
      }
    }
    return $defaults;
  }
}
