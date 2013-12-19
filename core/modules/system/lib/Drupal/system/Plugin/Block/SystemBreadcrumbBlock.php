<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemBreadcrumbBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides a block to display the breadcrumbs.
 *
 * @Block(
 *   id = "system_breadcrumb_block",
 *   admin_label = @Translation("Breadcrumbs")
 * )
 */
class SystemBreadcrumbBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $breadcrumb_manager = \Drupal::service('breadcrumb');
    $request = \Drupal::service('request');
    $breadcrumb = $breadcrumb_manager->build($request->attributes->all());
    if (!empty($breadcrumb)) {
      // $breadcrumb is expected to be an array of rendered breadcrumb links.
      return array(
        '#theme' => 'breadcrumb',
        '#breadcrumb' => $breadcrumb,
      );
    }
  }

}
