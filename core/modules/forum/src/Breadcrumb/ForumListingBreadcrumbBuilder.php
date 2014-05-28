<?php

/**
 * @file
 * Contains \Drupal\forum\Breadcrumb\ForumListingBreadcrumbBuilder.
 */

namespace Drupal\forum\Breadcrumb;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a breadcrumb builder base class for forum listing pages.
 */
class ForumListingBreadcrumbBuilder extends ForumBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return !empty($attributes[RouteObjectInterface::ROUTE_NAME])
      && $attributes[RouteObjectInterface::ROUTE_NAME] == 'forum.page'
      && isset($attributes['taxonomy_term']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb = parent::build($attributes);

    // Add all parent forums to breadcrumbs.
    $term_id = $attributes['taxonomy_term']->id();
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
