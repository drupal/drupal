<?php

namespace Drupal\forum\Breadcrumb;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a breadcrumb builder base class for forum listing pages.
 */
class ForumListingBreadcrumbBuilder extends ForumBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'forum.page' && $route_match->getParameter('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);
    $breadcrumb->addCacheContexts(['route']);

    // Add all parent forums to breadcrumbs.
    /** @var \Drupal\Taxonomy\TermInterface $term */
    $term = $route_match->getParameter('taxonomy_term');
    $term_id = $term->id();
    $breadcrumb->addCacheableDependency($term);

    $parents = $this->forumManager->getParents($term_id);
    if ($parents) {
      foreach (array_reverse($parents) as $parent) {
        if ($parent->id() != $term_id) {
          $breadcrumb->addCacheableDependency($parent);
          $breadcrumb->addLink(Link::createFromRoute($parent->label(), 'forum.page', [
            'taxonomy_term' => $parent->id(),
          ]));
        }
      }
    }

    return $breadcrumb;
  }

}
