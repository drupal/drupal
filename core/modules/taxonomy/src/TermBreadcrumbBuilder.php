<?php

/**
 * @file
 * Contains of \Drupal\taxonomy\TermBreadcrumbBuilder.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'taxonomy.term_page'
      && $route_match->getParameter('taxonomy_term') instanceof TermInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $term = $route_match->getParameter('taxonomy_term');
    // @todo This overrides any other possible breadcrumb and is a pure
    //   hard-coded presumption. Make this behavior configurable per
    //   vocabulary or term.
    $breadcrumb = array();
    while ($parents = taxonomy_term_load_parents($term->id())) {
      $term = array_shift($parents);
      $breadcrumb[] = $this->l($term->getName(), 'taxonomy.term_page', array('taxonomy_term' => $term->id()));
    }
    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $breadcrumb = array_reverse($breadcrumb);

    return $breadcrumb;
  }

}
