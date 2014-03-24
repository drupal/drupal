<?php

/**
 * @file
 * Contains \Drupal\forum\Forum\Breadcrumb\ForumNodeBreadcrumbBuilder.
 */

namespace Drupal\forum\Breadcrumb;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Breadcrumb builder for forum nodes.
 */
class ForumNodeBreadcrumbBuilder extends ForumBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return !empty($attributes[RouteObjectInterface::ROUTE_NAME])
      && $attributes[RouteObjectInterface::ROUTE_NAME] == 'node.view'
      && isset($attributes['node'])
      && $this->forumManager->checkNodeType($attributes['node']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb = parent::build($attributes);

    $parents = $this->forumManager->getParents($attributes['node']->forum_tid);
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
