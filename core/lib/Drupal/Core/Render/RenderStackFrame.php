<?php

/**
 * @file
 * Contains \Drupal\Core\Render\RenderStackFrame.
 */

namespace Drupal\Core\Render;

/**
 * Value object used for bubbleable rendering metadata.
 *
 * @see drupal_render()
 */
class RenderStackFrame {

  /**
   * Cache tags.
   *
   * @var array
   */
  public $tags = [];

  /**
   * Attached assets.
   *
   * @var array
   */
  public $attached = [];

  /**
   * #post_render_cache metadata.
   *
   * @var array
   */
  public $postRenderCache = [];

}
