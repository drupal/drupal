<?php

namespace Drupal\settings_tray_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block that explicitly provides no "settings_tray" form, thus opting out.
 *
 * @Block(
 *   id = "settings_tray_test_false",
 *   admin_label = "Settings Tray test block: forms[settings_tray]=FALSE",
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class SettingsTrayFormAnnotationIsFalseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>FALSE</span>'];
  }

}
