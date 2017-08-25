<?php

namespace Drupal\outside_in_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block that explicitly provides a "settings_tray" form class.
 *
 * @Block(
 *   id = "outside_in_test_class",
 *   admin_label = "Settings Tray test block: forms[settings_tray]=class",
 *   forms = {
 *     "settings_tray" = "\Drupal\outside_in_test\Form\SettingsTrayFormAnnotationIsClassBlockForm",
 *   },
 * )
 */
class SettingsTrayFormAnnotationIsClassBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>class</span>'];
  }

}
