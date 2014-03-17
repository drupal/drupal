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
  public function applies(array $attributes) {
    return !empty($attributes[RouteObjectInterface::ROUTE_NAME])
    && ($attributes[RouteObjectInterface::ROUTE_NAME] == 'taxonomy.term_page')
    && ($attributes['taxonomy_term'] instanceof TermInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $term = $attributes['taxonomy_term'];
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
