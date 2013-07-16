<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Access\TaxonomyTermCreateAccess.
 */

namespace Drupal\taxonomy\Access;

use Drupal\Core\Entity\EntityCreateAccessCheck;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for taxonomy term creation.
 */
class TaxonomyTermCreateAccess extends EntityCreateAccessCheck {

  /**
   * {@inheritdoc}
   */
  protected $requirementsKey = '_access_taxonomy_term_create';

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    $entity_type = $route->getRequirement($this->requirementsKey);
    if ($vocabulary = $request->attributes->get('taxonomy_vocabulary')) {
      return $this->entityManager->getAccessController($entity_type)->createAccess($vocabulary->id());
    }
    return parent::access($route, $request);
  }

}
