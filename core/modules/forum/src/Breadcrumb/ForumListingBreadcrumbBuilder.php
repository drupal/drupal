<?php

/**
 * @file
 * Contains \Drupal\forum\Breadcrumb\ForumListingBreadcrumbBuilder.
 */

namespace Drupal\forum\Breadcrumb;

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

    // Add all parent forums to breadcrumbs.
    $term_id = $route_match->getParameter('taxonomy_term')->id();
    $parents = $this->forumManager->getParents($term_id);
    if ($parents) {
      foreach (array_reverse($parents) as $parent) {
        if ($parent->id() != $term_id) {
          $breadcrumb[] = $this->l($parent->label(), 'forum.page', array(
            'taxonomy_term' => $parent->id(),
          ));
        }
      }
    }
    return $breadcrumb;
  }

}
