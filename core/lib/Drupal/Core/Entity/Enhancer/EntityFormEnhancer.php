<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Enhancer\EntityFormEnhancer.
 */

namespace Drupal\Core\Entity\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class EntityFormEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\CoreContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new \Drupal\Core\Entity\Enhancer\EntityFormEnhancer.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation library.
   */
  public function __construct(ContentNegotiation $negotiation) {
    $this->negotiation = $negotiation;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_controller']) && !empty($defaults['_entity_form']) && $this->negotiation->getContentType($request) === 'html') {
      $defaults['_controller'] = '\Drupal\Core\Entity\HtmlEntityFormController::content';
    }
    return $defaults;
  }

}
