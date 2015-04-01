<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\LanguagesCacheContext.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the LanguagesCacheContext service, for "per language" caching.
 */
class LanguagesCacheContext implements CalculatedCacheContextInterface  {

  /**
   * The language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Constructs a new LanguagesCacheContext service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Language');
  }

  /**
   * {@inheritdoc}
   *
   * $type can be NULL, or one of the language types supported by the language
   * manager, typically:
   * - LanguageInterface::TYPE_INTERFACE
   * - LanguageInterface::TYPE_CONTENT
   * - LanguageInterface::TYPE_URL
   *
   * @see \Drupal\Core\Language\LanguageManagerInterface::getLanguageTypes()
   *
   * @throws \RuntimeException
   *   In case an invalid language type is specified.
   */
  public function getContext($type = NULL) {
    if ($type === NULL) {
      $context_parts = array();
      if ($this->languageManager->isMultilingual()) {
        foreach ($this->languageManager->getLanguageTypes() as $type) {
          $context_parts[] = $this->languageManager->getCurrentLanguage($type)->getId();
        }
      }
      else {
        $context_parts[] = $this->languageManager->getCurrentLanguage()->getId();
      }
      return implode(',', $context_parts);
    }
    else {
      $language_types = $this->languageManager->getDefinedLanguageTypesInfo();
      if (!isset($language_types[$type])) {
        throw new \RuntimeException(sprintf('The language type "%s" is invalid.', $type));
      }
      return $this->languageManager->getCurrentLanguage($type)->getId();
    }
  }

}
