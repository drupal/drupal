<?php

declare(strict_types=1);

namespace Drupal\ckeditor5\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade path for contrib plugins now in core.
 *
 * @CKEditor4To5Upgrade(
 *   id = "contrib",
 *   cke4_buttons = {
 *     "Code"
 *   },
 *   cke4_plugin_settings = {
 *   },
 *   cke5_plugin_elements_subset_configuration = {
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class Contrib extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    switch ($cke4_button) {
      // @see https://www.drupal.org/project/codetag
      case 'Code':
        return ['code'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
