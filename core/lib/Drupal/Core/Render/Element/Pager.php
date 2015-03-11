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
    ];
  }

  /**
   * #pre_render callback to associate the appropriate cache context.
   *
   * @param array $pager
   *   A renderable array of #type => pager.
   *
   * @return array
   */
  public static function preRenderPager(array $pager) {
    $pager['#cache']['contexts'][] = 'pager:' . $pager['#element'];
    return $pager;
  }

}
