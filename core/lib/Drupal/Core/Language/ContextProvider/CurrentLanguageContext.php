<?php

namespace Drupal\Core\Language\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current language as a context.
 */
class CurrentLanguageContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CurrentLanguageContext.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Add a context for each language type.
    $language_types = $this->languageManager->getLanguageTypes();
    $info = $this->languageManager->getDefinedLanguageTypesInfo();

    if ($unqualified_context_ids) {
      foreach ($unqualified_context_ids as $unqualified_context_id) {
        if (array_search($unqualified_context_id, $language_types) === FALSE) {
          unset($language_types[$unqualified_context_id]);
        }
      }
    }

    $result = [];
    foreach ($language_types as $type_key) {
      if (isset($info[$type_key]['name'])) {
        $context = new Context(new ContextDefinition('language', $info[$type_key]['name']), $this->languageManager->getCurrentLanguage($type_key));

        $cacheability = new CacheableMetadata();
        $cacheability->setCacheContexts(['languages:' . $type_key]);
        $context->addCacheableDependency($cacheability);

        $result[$type_key] = $context;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
