<?php

/**
 * @file
 * Contains \Drupal\comment\Routing\CommentBundleEnhancer.
 */

namespace Drupal\comment\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Constructs a route enhancer to extract values from comment bundles.
 *
 * Comment bundle names are of the form {entity_type}__{field_name}. This
 * enhancer extracts them from the path and makes them available as arguments
 * to controllers.
 */
class CommentBundleEnhancer implements RouteEnhancerInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a CommentBundleEnhancer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (isset($defaults['bundle']) && ($bundles = $this->entityManager->getBundleInfo('comment')) && isset($bundles[$defaults['bundle']])) {
      list($entity_type, $field_name) = explode('__', $defaults['bundle'], 2);
      $defaults['commented_entity_type'] = $entity_type;
      $defaults['field_name'] = $field_name;
    }
    return $defaults;
  }

}
