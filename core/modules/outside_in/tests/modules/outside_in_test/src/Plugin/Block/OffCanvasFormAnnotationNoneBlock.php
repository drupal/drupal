<?php

namespace Drupal\outside_in_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block that does nothing explicit for Settings Tray.
 *
 * @Block(
 *   id = "outside_in_test_none",
 *   admin_label = "Settings Tray test block: forms[off_canvas] is not specified",
 * )
 */
class OffCanvasFormAnnotationNoneBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>none</span>'];
  }

}
