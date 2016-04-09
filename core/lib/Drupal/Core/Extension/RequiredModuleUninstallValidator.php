<?php

namespace Drupal\Core\Extension;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Ensures that required modules cannot be uninstalled.
 */
class RequiredModuleUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new RequiredModuleUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    $module_info = $this->getModuleInfoByModule($module);
    if (!empty($module_info['required'])) {
      $reasons[] = $this->t('The @module module is required', ['@module' => $module_info['name']]);
    }
    return $reasons;
  }

  /**
   * Returns the module info for a specific module.
   *
   * @param string $module
   *   The name of the module.
   *
   * @return array
   *   The module info, or NULL if that module does not exist.
   */
  protected function getModuleInfoByModule($module) {
    $modules = system_rebuild_module_data();
    return isset($modules[$module]->info) ? $modules[$module]->info : [];
  }

}
