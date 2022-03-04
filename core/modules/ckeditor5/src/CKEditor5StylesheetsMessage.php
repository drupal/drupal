<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Messaging for themes using the ckeditor_stylesheets setting.
 *
 * Messaging is provided when themes are found that use ckeditor_stylesheets
 * without a corresponding ckeditor5-stylesheets setting.
 *
 * @internal
 *   This class may change at any time. It is not for use outside this module.
 * @todo Remove in Drupal 11: https://www.drupal.org/project/ckeditor5/issues/3239012
 */
final class CKEditor5StylesheetsMessage {

  use StringTranslationTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CKEditor5StylesheetsMessage.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * Generates a warning related to ckeditor_stylesheets.
   *
   * Identifies themes using ckeditor_stylesheets without an equivalent
   * ckeditor5-stylesheets setting. If such themes are found, a warning message
   * is returned.
   *
   * @return \Drupal\Core\StringTranslation\PluralTranslatableMarkup|null
   *   A warning message where appropriate, otherwise null.
   */
  public function getWarning() {
    $themes = [];
    $default_theme = $this->configFactory->get('system.theme')->get('default');
    if (!empty($default_theme)) {
      $themes[$default_theme] = $this->themeHandler->listInfo()[$default_theme]->info;
    }

    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    if (!empty($admin_theme) && $admin_theme !== $default_theme) {
      $themes[$admin_theme] = $this->themeHandler->listInfo()[$admin_theme]->info;
    }

    // Collect information on which themes/base themes have ckeditor_stylesheets
    // configuration, but do not have corresponding ckeditor5-stylesheets
    // configuration.
    $ckeditor_stylesheets_use = [];
    foreach ($themes as $theme_info) {
      $this->checkForStylesheetsEquivalent($theme_info, $ckeditor_stylesheets_use);
    }

    if (!empty($ckeditor_stylesheets_use)) {
      // A subtheme may unnecessarily appear multiple times.
      $ckeditor_stylesheets_use = array_unique($ckeditor_stylesheets_use);
      $last_item = array_pop($ckeditor_stylesheets_use);
      $stylesheets_warning = $this->formatPlural(count($ckeditor_stylesheets_use) + 1,
        'The %last_item theme has ckeditor_stylesheets configured without a corresponding ckeditor5-stylesheets configuration. See the <a href=":change_record">change record</a> for details.',
        'The %first_items and %last_item themes have ckeditor_stylesheets configured, but without corresponding ckeditor5-stylesheets configurations. See the <a href=":change_record">change record</a> for details.',
        [
          '%last_item' => $last_item,
          '%first_items' => implode(', ', $ckeditor_stylesheets_use),
          ':change_record' => 'https://www.drupal.org/node/3259165',
        ]);

      return $stylesheets_warning;
    }

    return NULL;
  }

  /**
   * Checks themes using ckeditor_stylesheets for CKEditor 5 equivalents.
   *
   * @param array $theme_info
   *   The config of the theme to check.
   * @param string[] $ckeditor_stylesheets_use
   *   Themes using ckeditor_stylesheets without a CKEditor 5 equivalent.
   */
  private function checkForStylesheetsEquivalent(array $theme_info, array &$ckeditor_stylesheets_use) {
    $theme_has_ckeditor5_stylesheets = isset($theme_info['ckeditor5-stylesheets']);
    if (!empty($theme_info['ckeditor_stylesheets']) && !$theme_has_ckeditor5_stylesheets) {
      $ckeditor_stylesheets_use[] = $theme_info['name'];
    }

    // If the primary theme has ckeditor5-stylesheets configured, do not check
    // base themes. The primary theme can potentially provide the
    // ckeditor5-stylesheets config for itself and its base themes, so we err
    // on the side of not showing a warning if this is possibly the case.
    if ($theme_has_ckeditor5_stylesheets) {
      return;
    }
    $base_theme = $theme_info['base theme'] ?? FALSE;
    while ($base_theme) {
      $base_theme_info = $this->themeHandler->listInfo()[$base_theme]->info;
      $base_theme_has_ckeditor5_stylesheets = isset($base_theme_info['ckeditor5-stylesheets']);

      if (!empty($base_theme_info['ckeditor_stylesheets']) && !$base_theme_has_ckeditor5_stylesheets) {
        $ckeditor_stylesheets_use[] = $base_theme_info['name'];
      }
      $base_theme = $base_theme_info['base theme'] ?? FALSE;
    }
  }

}
