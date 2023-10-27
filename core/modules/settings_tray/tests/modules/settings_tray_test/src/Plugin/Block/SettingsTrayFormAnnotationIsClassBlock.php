<?php

namespace Drupal\settings_tray_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\settings_tray_test\Form\SettingsTrayFormAnnotationIsClassBlockForm;

/**
 * Block that explicitly provides a "settings_tray" form class.
 */
#[Block(
  id: "settings_tray_test_class",
  admin_label: new TranslatableMarkup("Settings Tray test block: forms[settings_tray]=class"),
  forms: [
    'settings_tray' => SettingsTrayFormAnnotationIsClassBlockForm::class,
  ]
)]
class SettingsTrayFormAnnotationIsClassBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span>class</span>'];
  }

}
