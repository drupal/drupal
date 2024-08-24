<?php

declare(strict_types=1);

namespace Drupal\settings_tray_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Block that explicitly provides no "settings_tray" form, thus opting out.
 */
#[Block(
  id: "settings_tray_test_false",
  admin_label: new TranslatableMarkup("Settings Tray test block: forms[settings_tray]=FALSE"),
  forms: [
    'settings_tray' => FALSE,
  ]
)]
class SettingsTrayFormAnnotationIsFalseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>FALSE</span>'];
  }

}
