<?php

/**
 * @file
 * Contains of \Drupal\taxonomy\TermBreadcrumbBuilder.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'taxonomy.term_page' && ($term = $attributes['taxonomy_term']) && $term instanceof TermInterface) {
      // @todo This overrides any other possible breadcrumb and is a pure
      //   hard-coded presumption. Make this behavior configurable per
      //   vocabulary or term.
      $breadcrumb = array();
      while ($parents = taxonomy_term_load_parents($term->id())) {
        $term = array_shift($parents);
        $breadcrumb[] = $this->l($term->label(), 'taxonomy.term_page', array('taxonomy_term' => $term->id()));
      }
      $breadcrumb[] = $this->l($this->t('Home'), '<front>');
      $breadcrumb = array_reverse($breadcrumb);

      return $breadcrumb;
    }
  }

}
