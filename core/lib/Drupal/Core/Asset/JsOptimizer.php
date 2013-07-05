<?php

/**
 * Contains \Drupal\Core\Asset\JsOptimizer.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Asset\AssetOptimizerInterface;

/**
 * Optimizes a JavaScript asset.
 */
class JsOptimizer implements AssetOptimizerInterface {

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_asset) {
    if ($js_asset['type'] !== 'file') {
      throw new \Exception('Only file JavaScript assets can be optimized.');
    }
    if ($js_asset['type'] === 'file' && !$js_asset['preprocess']) {
      throw new \Exception('Only file JavaScript assets with preprocessing enabled can be optimized.');
    }

    // No-op optimizer: no optimizations are applied to JavaScript assets.
    return file_get_contents($js_asset['data']);
  }

}
