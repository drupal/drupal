<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Pager.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a render element for a pager.
 *
 * @RenderElement("pager")
 */
class Pager extends RenderElement{

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        get_class($this) . '::preRenderPager',
      ],
      '#theme' => 'pager',
      // The pager ID, to distinguish between multiple pagers on the same page.
      '#element' => 0,
      // An associative array of query string parameters to append to the pager
      // links.
      '#parameters' => [],
      // The number of pages in the list.
      '#quantity' => 9,
      // An array of labels for the controls in the pager.
      '#tags' => [],
      // The name of the route to be used to build pager links. By default no
      // path is provided, which will make links relative to the current URL.
      // This makes the page more effectively cacheable.
      '#route_name' => '<none>',
    ];
  }

  /**
   * #pre_render callback to associate the appropriate cache context.
   *
   *
   * @param array $pager
   *   A renderable array of #type => pager.
   *
   * @return array
   */
  public static function preRenderPager(array $pager) {
    // Note: the default pager theme process function
    // template_preprocess_pager() also calls pager_query_add_page(), which
    // maintains the existing query string. Therefore
    // template_preprocess_pager() adds the 'url.query_args' cache context,
    // which causes the more specific cache context below to be optimized away.
    // In other themes, however, that may not be the case.
    $pager['#cache']['contexts'][] = 'url.query_args.pagers:' . $pager['#element'];
    return $pager;
  }

}
