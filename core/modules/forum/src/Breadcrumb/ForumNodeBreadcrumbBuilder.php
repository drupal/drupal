<?php

/**
 * @file
 * Contains \Drupal\forum\Forum\Breadcrumb\ForumNodeBreadcrumbBuilder.
 */

namespace Drupal\forum\Breadcrumb;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Breadcrumb builder for forum nodes.
 */
class ForumNodeBreadcrumbBuilder extends ForumBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'node.view'
      && $route_match->getParameter('node')
      && $this->forumManager->checkNodeType($route_match->getParameter('node'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);

    $parents = $this->forumManager->getParents($route_match->getParameter('node')->forum_tid);
    if ($parents) {
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb[] = $this->l($parent->label(), 'forum.page',
          array(
            'taxonomy_term' => $parent->id(),
          )
        );
      }
    }
    return $breadcrumb;
  }

}
