<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Access\TaxonomyTermCreateAccess.
 */

namespace Drupal\taxonomy\Access;

use Drupal\Core\Entity\EntityCreateAccessCheck;
use Symfony\Component\HttpFoundation\Request;

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
  protected function prepareEntityValues(array $definition, Request $request, $bundle = NULL) {
    $values = array();
    if ($vocabulary = $request->attributes->get('taxonomy_vocabulary')) {
      $values = parent::prepareEntityValues($definition, $request, $vocabulary->id());
    }
    return $values;
  }

}
