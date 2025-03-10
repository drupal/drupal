<?php

declare(strict_types=1);

namespace Drupal\block_test;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Implements a trusted preRender callback.
 */
class BlockRenderAlterContent implements RenderCallbackInterface {

  /**
   * Render API callback: Alters the content of a block.
   *
   * This function is assigned as a #pre_render callback.
   */
  public static function preRender(array $build) {
    $build['#prefix'] = 'Hiya!<br>';
    return $build;
  }

}
