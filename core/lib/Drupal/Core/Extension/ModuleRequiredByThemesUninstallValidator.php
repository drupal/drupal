<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Ensures modules cannot be uninstalled if enabled themes depend on them.
 */
class ModuleRequiredByThemesUninstallValidator implements ConfigImportModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a new ModuleRequiredByThemesUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $extension_list_theme
   *   The theme extension list.
   */
  public function __construct(TranslationInterface $string_translation, ModuleExtensionList $extension_list_module, ThemeExtensionList $extension_list_theme) {
    $this->stringTranslation = $string_translation;
    $this->moduleExtensionList = $extension_list_module;
    $this->themeExtensionList = $extension_list_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];

    $themes_depending_on_module = $this->getThemesDependingOnModule($module);
    if (!empty($themes_depending_on_module)) {
      $module_name = $this->moduleExtensionList->get($module)->info['name'];
      $theme_names = implode(', ', $themes_depending_on_module);
      $reasons[] = $this->formatPlural(count($themes_depending_on_module),
        'Required by the theme: @theme_names',
        'Required by the themes: @theme_names',
        ['@module_name' => $module_name, '@theme_names' => $theme_names]);
    }

    return $reasons;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigImport(string $module, StorageInterface $source_storage): array {
    $reasons = [];

    $themes_depending_on_module = $this->getThemesDependingOnModule($module);
    if (!empty($themes_depending_on_module)) {
      $installed_themes_after_import = $source_storage->read('core.extension')['theme'];
      $themes_depending_on_module_still_installed = array_intersect_key($themes_depending_on_module, $installed_themes_after_import);
      // Ensure that any dependent themes will be uninstalled by the module.
      if (!empty($themes_depending_on_module_still_installed)) {
        $reasons[] = $this->formatPlural(count($themes_depending_on_module_still_installed),
          'Required by the theme: @theme_names',
          'Required by the themes: @theme_names',
          ['@theme_names' => implode(', ', $themes_depending_on_module_still_installed)]);
      }
    }
    return $reasons;
  }

  /**
   * Returns themes that depend on a module.
   *
   * @param string $module
   *   The module machine name.
   *
   * @return string[]
   *   An array of the names of themes that depend on $module keyed by the
   *   theme's machine name.
   */
  protected function getThemesDependingOnModule($module) {
    $installed_themes = $this->themeExtensionList->getAllInstalledInfo();
    $themes_depending_on_module = array_map(function ($theme) use ($module) {
      if (in_array($module, $theme['dependencies'])) {
        return $theme['name'];
      }
    }, $installed_themes);

    return array_filter($themes_depending_on_module);
  }

}
