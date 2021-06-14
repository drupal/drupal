<?php

namespace Drupal\block_test;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Implements a trusted preRender callback.
 */
class BlockRenderAlterContent implements RenderCallbackInterface {

  /**
   * #pre_render callback for a block to alter its content.
   */
  public static function preRender(array $build) {
    $build['#prefix'] = 'Hiya!<br>';
    return $build;
  }

}
