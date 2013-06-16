<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
 */

namespace Drupal\Core\Entity\Enhancer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\Core\ContentNegotiation;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class EntityRouteEnhancer implements RouteEnhancerInterface {

  /**
   * Content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * Constructs a new \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
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
    if (empty($defaults['_controller']) && $this->negotiation->getContentType($request) === 'html') {
      if (!empty($defaults['_entity_form'])) {
        $defaults['_controller'] = '\Drupal\Core\Entity\HtmlEntityFormController::content';
      }
      elseif (!empty($defaults['_entity_list'])) {
        $defaults['_controller'] = 'controller.page:content';
        $defaults['_content'] = '\Drupal\Core\Entity\Controller\EntityListController::listing';
        $defaults['entity_type'] = $defaults['_entity_list'];
        unset($defaults['_entity_list']);
      }
    }
    return $defaults;
  }

}
