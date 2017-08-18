<?php

namespace Drupal\outside_in_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block that explicitly provides an "off_canvas" form class.
 *
 * @Block(
 *   id = "outside_in_test_class",
 *   admin_label = "Settings Tray test block: forms[off_canvas]=class",
 *   forms = {
 *     "off_canvas" = "\Drupal\outside_in_test\Form\OffCanvasFormAnnotationIsClassBlockForm",
 *   },
 * )
 */
class OffCanvasFormAnnotationIsClassBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>class</span>'];
  }

}
