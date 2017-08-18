<?php

namespace Drupal\outside_in_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block that explicitly provides no "off_canvas" form, thus opting out.
 *
 * @Block(
 *   id = "outside_in_test_false",
 *   admin_label = "Settings Tray test block: forms[off_canvas]=FALSE",
 *   forms = {
 *     "off_canvas" = FALSE,
 *   },
 * )
 */
class OffCanvasFormAnnotationIsFalseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>FALSE</span>'];
  }

}
