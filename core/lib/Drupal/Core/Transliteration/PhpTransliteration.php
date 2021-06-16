<?php

namespace Drupal\Core\Transliteration;

use Drupal\Component\Transliteration\PhpTransliteration as BaseTransliteration;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Enhances PhpTransliteration with an alter hook.
 *
 * @ingroup transliteration
 * @see hook_transliteration_overrides_alter()
 */
class PhpTransliteration extends BaseTransliteration {

  /**
   * The module handler to execute the transliteration_overrides alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a PhpTransliteration object.
   *
   * @param string $data_directory
   *   The directory where data files reside. If NULL, defaults to subdirectory
   *   'data' underneath the directory where the class's PHP file resides.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to execute the transliteration_overrides alter hook.
   */
  public function __construct($data_directory, ModuleHandlerInterface $module_handler) {
    parent::__construct($data_directory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * Overrides \Drupal\Component\Transliteration\PhpTransliteration::readLanguageOverrides().
   *
   * Allows modules to alter the language-specific $overrides array by invoking
   * hook_transliteration_overrides_alter().
   */
  protected function readLanguageOverrides($langcode) {
    parent::readLanguageOverrides($langcode);

    // Let modules alter the language-specific overrides.
    $this->moduleHandler->alter('transliteration_overrides', $this->languageOverrides[$langcode], $langcode);
  }

}
