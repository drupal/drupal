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
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new RequiredModuleUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(TranslationInterface $string_translation, ModuleExtensionList $extension_list_module) {
    $this->stringTranslation = $string_translation;
    $this->moduleExtensionList = $extension_list_module;
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
   *   The module info, or empty array if that module does not exist.
   */
  protected function getModuleInfoByModule($module) {
    if ($this->moduleExtensionList->exists($module)) {
      return $this->moduleExtensionList->get($module)->info;
    }
    return [];
  }

}
