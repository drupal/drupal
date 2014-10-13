<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\LanguageCacheContext.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the LanguageCacheContext service, for "per language" caching.
 */
class LanguageCacheContext implements CacheContextInterface {

  /**
   * The language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageCacheContext service.
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
   */
  public function getContext() {
    $context_parts = array();
    if ($this->languageManager->isMultilingual()) {
      foreach ($this->languageManager->getLanguageTypes() as $type) {
        $context_parts[] = $this->languageManager->getCurrentLanguage($type)->getId();
      }
    }
    return implode(':', $context_parts);
  }

}
